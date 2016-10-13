<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'CompareTrait.php';
require_once 'CompareQueries.php';

use Enjoin\Factory;
use Enjoin\Enjoin;
use Enjoin\Record\Record;
use Enjoin\Exceptions\ValidationException;
use Carbon\Carbon;

class EnjoinTest extends PHPUnit_Framework_TestCase
{

    use CompareTrait;

    private $debugFunction = 'testModelUpdateWithoutWhere';

    public function testBootstrap()
    {
        Factory::bootstrap([
            'database' => [
                'default' => 'test',
                'connections' => [
                    'test' => [
                        'driver' => 'mysql',
                        'host' => 'localhost',
                        'database' => getenv('ENJ_DATABASE'),
                        'username' => getenv('ENJ_USERNAME'),
                        'password' => getenv('ENJ_PASSWORD'),
                        'charset' => 'utf8',
                        'collation' => 'utf8_unicode_ci',
                        'prefix' => ''
                    ]
                ],
                'redis' => [
                    'cluster' => false,
                    'default' => [
                        'host' => '127.0.0.1',
                        'port' => 6379,
                        'database' => 0,
                    ]
                ]
            ],
            'enjoin' => [
                'lang_dir' => 'vendor/caouecs/laravel-lang'
            ],
            'cache' => [
                'default' => 'redis',
                'stores' => [
                    'redis' => [
                        'driver' => 'redis',
                        'connection' => 'default'
                    ]
                ],
                'prefix' => 'enjoin_test'
            ]
        ]);
    }

    /**
     * @depends testBootstrap
     */
    public function testEnjoinGet()
    {
        Enjoin::get('Authors');
        $this->assertArrayHasKey('\Models\Authors', Factory::getInstance()->models);
    }

    /**
     * @depends testBootstrap
     * @return Record
     */
    public function testModelBuild()
    {
        $collection = new stdClass;
        $collection->name = 'J. R. R. Tolkien';
        $it = Enjoin::get('Authors')->build($collection);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
        return $it;
    }

    /**
     * @depends testModelBuild
     * @param Record $it
     */
    public function testNonPersistentRecordSave(Record $it)
    {
        $it->save();
        $this->assertEquals(1, $it->id);
    }

    /**
     * @depends testBootstrap
     * @return Record
     */
    public function testNonPersistentNestedRecordSave()
    {
        $it = Enjoin::get('Authors')->build([
            'name' => 'George Orwell',
            'book' => Enjoin::get('Books')->build([
                'title' => 'Nineteen Eighty Four',
                'year' => 1942
            ])
        ]);
        $it->save();
        $this->assertEquals([2, 1], [$it->id, $it->book->id]);
        return $it;
    }

    /**
     * @depends testNonPersistentNestedRecordSave
     * @param Record $it
     */
    public function testPersistentRecordSave(Record $it)
    {
        $authorName = 'G. Orwell';
        $bookAuthorId = 2;

        $it->name = $authorName;
        $it->book->authors_id = $bookAuthorId;
        $it->save();
        $this->assertEquals([$authorName, $bookAuthorId], [$it->name, $it->book->authors_id]);
    }

    /**
     * @depends testNonPersistentNestedRecordSave
     * @param Record $it
     */
    public function testRecordValidation(Record $it)
    {
        $year = $it->book->year;
        $it->book->year = 3000;
        try {
            $it->book->save();
        } catch (ValidationException $e) {
            $it->book->year = $year;
        }
        $this->assertEquals($year, $it->book->year);
    }

    /**
     * @depends testBootstrap
     * @return Record
     */
    public function testModelCreate()
    {
        $it = Enjoin::get('Publishers')->create(['name' => 'Good Books!']);
        $this->assertEquals(1, $it->id);
        return $it;
    }

    /**
     * @depends testBootstrap
     */
    public function testModelCreateEmpty()
    {
        $this->handleDebug(__FUNCTION__);
        $a = Enjoin::get('Languages')->create();
        $b = Enjoin::get('Languages')->create([]);
        $this->assertEquals([1, 2], [$a->id, $b->id]);
    }

    /**
     * @depends testBootstrap
     */
    public function testModelCreateWithDateField()
    {
        $this->handleDebug(__FUNCTION__);
        $collection = ['date_till' => new Carbon];
        $created = Enjoin::get('Pile')->create($collection);
        $it = Enjoin::get('Pile')->findById($created->id);
        $this->assertTrue($created->date_till->eq($it->date_till));
    }

    /**
     * @depends testModelBuild
     * @param Record $author
     * @return Record
     */
    public function testRecordAfterSaveMapping(Record $author)
    {
        $book = Enjoin::get('Books')->create([
            'title' => 'The Hobbit: or There and Back Again',
            'year' => 1937,
            'authors_id' => $author->id
        ]);
        $this->assertInstanceOf('Carbon\Carbon', $book->created_at);
        return $book;
    }

    /**
     * @depends testBootstrap
     */
    public function testFindById()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findById(1, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `id`, `name`, `created_at`, `updated_at` FROM `authors` AS `authors` WHERE `authors`.`id` = 1",
            $sql
        );
        $it = Enjoin::get('Authors')->findById(1);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
        $this->assertEquals([1, 'J. R. R. Tolkien'], [$it->id, $it->name]);
        $this->assertInstanceOf('Carbon\Carbon', $it->created_at);
        $this->assertInstanceOf('Carbon\Carbon', $it->updated_at);
    }

    /**
     * @depends testModelBuild
     * @param Record $author
     */
    public function testBulkCreateValidation(Record $author)
    {
        $bulk = [[
            'title' => 'testBulkCreateValidation',
            'year' => 3000,
            'authors_id' => $author->id
        ]];
        $passed = false;
        try {
            Enjoin::get('Books')->bulkCreate($bulk);
        } catch (ValidationException $e) {
            $passed = true;
        }
        $this->assertTrue($passed);
    }

    /**
     * @depends testModelBuild
     * @depends testBulkCreateValidation
     * @param Record $author
     */
    public function testBulkCreate(Record $author)
    {
        $bulk = [];
        foreach (array_slice($this->getDataArray('books'), 0, 20) as $book) {
            $book['authors_id'] = $author->id;
            $bulk [] = $book;
        }
        $this->assertTrue(Enjoin::get('Books')->bulkCreate($bulk));
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEager()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEager();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEager(), $sql);

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerRequired();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->sql_testFindOneEagerRequired(), $sql));

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerById()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerById();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerById(), $sql);

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertEquals([
            1, 'J. R. R. Tolkien', 21, 2, 'The Hobbit: or There and Back Again'
        ], [
            $it->id, $it->name, count($it->books), $it->books[0]->id, $it->books[0]->title
        ]);
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerByIdRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerByIdRequired();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerByIdRequired(), $sql);

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerByIdMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerByIdMean();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerByIdMean(), $sql);

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerMean();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerMean(), $sql);

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerMeanRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerMeanRequired();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->sql_testFindOneEagerMeanRequired(), $sql));

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerReversed()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerReversed();
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerReversed(), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerReversedRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerReversedRequired();
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerReversedRequired(), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerReversedById()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerReversedById();
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerReversedById(), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerReversedByIdRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerReversedByIdRequired();
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerReversedByIdRequired(), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerReversedByIdMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerReversedByIdMean();
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerReversedByIdMean(), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerReversedMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerReversedMean();
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerReversedMean(), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneEagerReversedMeanRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerReversedMeanRequired();
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerReversedMeanRequired(), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneComplex()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneComplex();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneComplex(), $sql);

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testFindOneAndOr()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneAndOr();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneAndOr(), $sql);

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testMockDataA()
    {
        $book = Enjoin::get('Books')->findById(2);
        $this->assertEquals('The Hobbit: or There and Back Again', $book->title);

        $bulk = [];
        foreach (array_slice($this->getDataArray('reviews'), 0, 25) as $review) {
            $review['books_id'] = $book->id;
            $bulk [] = $review;
        }
        $this->assertTrue(Enjoin::get('Reviews')->bulkCreate($bulk));

        $publisher = Enjoin::get('Publishers')->findById(1);
        $this->assertEquals('Good Books!', $publisher->name);

        $bulk = [];
        foreach (array_slice($this->getDataArray('publishers_books'), 0, 5) as $it) {
            $it['publishers_id'] = $publisher->id;
            $it['books_id'] = $book->id;
            $bulk [] = $it;
        }
        $this->assertTrue(Enjoin::get('PublishersBooks')->bulkCreate($bulk));
    }

    /**
     * @depends testMockDataA
     */
    public function testFindOneEagerMulti()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerMulti();
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerMulti(), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertEquals(
            [2, 'Nineteen Eighty Four', 'G. Orwell', [], []],
            [$it->authors_id, $it->title, $it->author->name, $it->reviews, $it->publishersBooks]
        );
    }

    /**
     * @depends testMockDataA
     */
    public function testFindOneEagerMultiRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerMultiRequired();
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->sql_testFindOneEagerMultiRequired(), $sql));

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertEquals([
            1, 1937, 'J. R. R. Tolkien',
            25, 'ac leo pellentesque ultrices mattis odio donec vitae nisi nam',
            5, 5000
        ], [
            $it->authors_id, $it->year, $it->author->name,
            count($it->reviews), $it->reviews[0]->resource,
            count($it->publishersBooks), $it->publishersBooks[0]->pressrun
        ]);
    }

    /**
     * @depends testMockDataA
     */
    public function testFindOneEagerMultiWhere()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerMultiWhere();
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->sql_testFindOneEagerMultiWhere(), $sql));

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertEquals([
            2, 1937, 'J. R. R. Tolkien',
            25, 'ac leo pellentesque ultrices mattis odio donec vitae nisi nam',
            3, 90000
        ], [
            $it->id, $it->year, $it->author->name,
            count($it->reviews), $it->reviews[0]->resource,
            count($it->publishersBooks), $it->publishersBooks[0]->pressrun
        ]);
    }

    /**
     * @depends testMockDataA
     */
    public function testFindOneEagerNested()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerNested();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->sql_testFindOneEagerNested(), $sql));

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertEquals([
            1, 'J. R. R. Tolkien',
            1, 'The Hobbit: or There and Back Again', 1937,
            25, 4
        ], [
            $it->id, $it->name,
            count($it->books), $it->books[0]->title, $it->books[0]->year,
            count($it->books[0]->reviews), count($it->books[0]->publishersBooks)
        ]);
    }

    /**
     * @depends testMockDataA
     */
    public function testFindOneEagerNestedById()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerNestedById();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->sql_testFindOneEagerNestedById(), $sql));

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testMockDataA
     */
    public function testFindOneEagerNestedMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerNestedMean();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->sql_testFindOneEagerNestedMean(), $sql));

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertEquals([
            1, 'J. R. R. Tolkien',
            1, 'The Hobbit: or There and Back Again', 1937,
            25, 4
        ], [
            $it->id, $it->name,
            count($it->books), $it->books[0]->title, $it->books[0]->year,
            count($it->books[0]->reviews), count($it->books[0]->publishersBooks)
        ]);
    }

    /**
     * @depends testMockDataA
     */
    public function testFindOneEagerNestedDeep()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerNestedDeep();
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->sql_testFindOneEagerNestedDeep(), $sql));

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @todo: see https://github.com/sequelize/sequelize/issues/3917
     * @depends testMockDataA
     */
    public function testFindOneEagerSelfNestedNoSubQuery()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindOneEagerSelfNestedNoSubQuery();
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindOneEagerSelfNestedNoSubQuery(), $sql);
    }

    /**
     * @depends testBootstrap
     */
    public function testExpanseModel()
    {
        $this->handleDebug(__FUNCTION__);
        $this->assertEquals('OK', Enjoin::get('Authors')->ping());
    }

    /**
     * @depends testMockDataA
     */
    public function testFindAll()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findAll(null, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `id`, `name`, `created_at`, `updated_at` FROM `authors` AS `authors`",
            $sql
        );

        $r = Enjoin::get('Authors')->findAll();
        $this->assertEquals(
            [2, 'J. R. R. Tolkien', 2],
            [count($r), $r[0]->name, $r[1]->id]
        );
    }

    /**
     * @depends testMockDataA
     */
    public function testFindAllEmptyList()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->findAll($this->params_testFindAllEmptyList(), Enjoin::SQL);
        $this->assertEquals($this->sql_testFindAllEmptyList(), $sql);
    }

    /**
     * @depends testMockDataA
     */
    public function testMockDataB()
    {
        $bulk = [];
        foreach ($this->getDataArray('articles') as $it) {
            $it['authors_id'] = 2;
            $bulk [] = $it;
        }
        $this->assertTrue(Enjoin::get('Articles')->bulkCreate($bulk));
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAllEagerOneThenMany()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindAllEagerOneThenMany();
        $sql = Enjoin::get('Books')->findAll($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindAllEagerOneThenMany(), $sql);

        $r = Enjoin::get('Books')->findAll($params);
        $this->assertEquals(
            [22, 1, 2, 12],
            [count($r), $r[0]->id, $r[0]->author->id, count($r[0]->author->articles)]
        );
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAllEagerOneThenManyMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindAllEagerOneThenManyMean();
        $sql = Enjoin::get('Books')->findAll($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindAllEagerOneThenManyMean(), $sql);

        $r = Enjoin::get('Books')->findAll($params);
        $this->assertEquals(
            [1, 1, 2, 12],
            [count($r), $r[0]->id, $r[0]->author->id, count($r[0]->author->articles)]
        );
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAllEagerOneThenManyMeanOrdered()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindAllEagerOneThenManyMeanOrdered();
        $sql = Enjoin::get('Books')->findAll($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindAllEagerOneThenManyMeanOrdered(), $sql);

        $r = Enjoin::get('Books')->findAll($params);
        $this->assertEquals(1980, $r[0]->author->articles[0]->year);
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAllEagerOneThenManyMeanGrouped()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindAllEagerOneThenManyMeanGrouped();
        $sql = Enjoin::get('Books')->findAll($params, Enjoin::SQL);
        $this->assertEquals($this->sql_testFindAllEagerOneThenManyMeanGrouped(), $sql);

        $r = Enjoin::get('Books')->findAll($params);
        $this->assertEquals(1928, $r[0]->author->articles[0]->year);
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAllEagerNestedDeep()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindAllEagerNestedDeep();
        $sql = Enjoin::get('Authors')->findAll($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->sql_testFindAllEagerNestedDeep(), $sql));
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAllEagerNestedDeepLimited()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->params_testFindAllEagerNestedDeepLimited();
        $sql = Enjoin::get('Authors')->findAll($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->sql_testFindAllEagerNestedDeepLimited(), $sql));
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAllEqArray()
    {
        $this->handleDebug(__FUNCTION__);
        $sqlEq = Enjoin::get('Books')->findAll([
            'where' => ['id' => [1, 2, 3]]
        ], Enjoin::SQL);
        $sqlIn = Enjoin::get('Books')->findAll([
            'where' => ['id' => ['in' => [1, 2, 3]]]
        ], Enjoin::SQL);
        $this->assertEquals($sqlEq, $sqlIn);
    }

    /**
     * @depends testMockDataB
     */
    public function testCount()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->count(null, Enjoin::SQL);
        $this->assertEquals("SELECT count(*) AS `count` FROM `authors` AS `authors`", $sql);

        $r = Enjoin::get('Authors')->count();
        $this->assertEquals(2, $r);
    }

    /**
     * @depends testMockDataB
     */
    public function testCountConditional()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->count($this->params_testCountConditional(), Enjoin::SQL);
        $this->assertEquals($this->sql_testCountConditional(), $sql);
    }

    /**
     * @depends testMockDataB
     */
    public function testCountEagerOneThenMany()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->count($this->params_testCountEagerOneThenMany(), Enjoin::SQL);
        $this->assertEquals($this->sql_testCountEagerOneThenMany(), $sql);
    }

    /**
     * @depends testMockDataB
     */
    public function testCountEagerOneThenManyMean()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->count($this->params_testCountEagerOneThenManyMean(), Enjoin::SQL);
        $this->assertEquals($this->sql_testCountEagerOneThenManyMean(), $sql);
    }

    /**
     * @depends testMockDataB
     */
    public function testCountEagerRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->count($this->params_testCountEagerRequired(), Enjoin::SQL);
        $this->assertEquals($this->sql_testCountEagerRequired(), $sql);
    }

    /**
     * @depends testMockDataB
     */
    public function testCountEagerRequiredLimited()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->count($this->params_testCountEagerRequiredLimited(), Enjoin::SQL);
        $this->assertEquals($this->sql_testCountEagerRequiredLimited(), $sql);
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAndCountAll()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findAndCountAll(null, Enjoin::SQL);
        $this->assertEquals("SELECT count(*) AS `count` FROM `authors` AS `authors`", $sql['count']);

        $r = Enjoin::get('Authors')->findAndCountAll();
        $this->assertEquals(
            [2, 'G. Orwell'],
            [$r['count'], $r['rows'][1]->name]
        );
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAndCountAllConditional()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->findAndCountAll($this->params_testFindAndCountAllConditional(), Enjoin::SQL);
        $this->assertEquals($this->sql_testFindAndCountAllConditional(), $sql['count']);
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAndCountAllEagerOneThenMany()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->findAndCountAll($this->params_testFindAndCountAllEagerOneThenMany(), Enjoin::SQL);
        $this->assertEquals($this->sql_testFindAndCountAllEagerOneThenMany(), $sql['count']);
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAndCountAllEagerOneThenManyMean()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->findAndCountAll($this->params_testFindAndCountAllEagerOneThenManyMean(), Enjoin::SQL);
        $this->assertEquals($this->sql_testFindAndCountAllEagerOneThenManyMean(), $sql['count']);
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAndCountAllEagerRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findAndCountAll($this->params_testFindAndCountAllEagerRequired(), Enjoin::SQL);
        $this->assertEquals($this->sql_testFindAndCountAllEagerRequired(), $sql['count']);
    }

    /**
     * @depends testMockDataB
     */
    public function testFindAndCountAllEagerRequiredLimited()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findAndCountAll($this->params_testFindAndCountAllEagerRequiredLimited(), Enjoin::SQL);
        $this->assertEquals($this->sql_testFindAndCountAllEagerRequiredLimited(), $sql['count']);
    }

    /**
     * @depends testMockDataB
     */
    public function testFindOrCreate()
    {
        $this->handleDebug(__FUNCTION__);
        $it = Enjoin::get('Books')->findOrCreate([
            'where' => [
                'title' => 'Animal Farm',
                'year' => 1945
            ],
            'defaults' => ['authors_id' => 2]
        ]);
        $this->assertEquals(
            [23, 'Animal Farm', 1945],
            [$it->id, $it->title, $it->year]
        );
    }

    /**
     * @depends testFindOrCreate
     */
    public function testRecordUpdate()
    {
        $this->handleDebug(__FUNCTION__);
        $it = Enjoin::get('Books')->findOrCreate([
            'where' => ['title' => 'Coming Up for'],
            'defaults' => ['authors_id' => 2]
        ]);
        $it->update([
            'title' => 'Coming Up for Air',
            'year' => 1939
        ], [
            'fields' => ['title', 'year']
        ]);
        $this->assertEquals([
            24, 'Coming Up for Air', 1939
        ], [
            $it->id, $it->title, $it->year
        ]);
    }

    /**
     * @depends testFindOrCreate
     */
    public function testCache()
    {
        $this->handleDebug(__FUNCTION__);
        $it = Enjoin::get('Books')->findById(1, Enjoin::WITH_CACHE);
        $cache = Factory::getCache()->tags('Models\Books')->get('9125bfc211f5ddbce7352499c9c71973');
        $this->assertEquals($it, $cache);
    }

    /**
     * @depends testCache
     */
    public function testCacheUpdate()
    {
        $this->handleDebug(__FUNCTION__);
        $params = [
            'where' => ['id' => 1],
            'include' => Enjoin::get('Authors')
        ];
        $it = Enjoin::get('Books')->findOne($params, Enjoin::WITH_CACHE);
        $cacheKey = Enjoin::get('Books')->CacheJar->keyify(['findOne', $params]);
        $cache = Factory::getCache()->tags('Models\Books')->get($cacheKey);
        $this->assertEquals($it, $cache);
        $cache->author->update(['name' => 'George Orwell']);
        $cache = Factory::getCache()->tags('Models\Books')->get($cacheKey);
        $this->assertNull($cache);
    }

    /**
     * @depends testCacheUpdate
     */
    public function testModelDestroy()
    {
        $this->handleDebug(__FUNCTION__);
        Enjoin::get('Authors')->findOrCreate([
            'where' => ['name' => 'Samuel Pepys']
        ]);
        $params = [
            'where' => ['name' => ['like' => 'Samuel%']]
        ];
        $sql = Enjoin::get('Authors')->destroy($params, Enjoin::SQL);
        $this->assertEquals("DELETE FROM `authors` WHERE `authors`.`name` LIKE 'Samuel%'", $sql);
        $affected = Enjoin::get('Authors')->destroy($params);
        $this->assertEquals(1, $affected);
    }

    /**
     * @depends testCacheUpdate
     */
    public function testModelDestroyWithoutWhere()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Languages')->destroy(null, Enjoin::SQL);
        $this->assertEquals("DELETE FROM `languages`", $sql);
    }

    /**
     * @depends testCacheUpdate
     */
    public function testModelUpdate()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Languages')->update($this->collection_testModelUpdate(), $this->params_testModelUpdate(), Enjoin::SQL);
        $this->assertEquals($this->sql_testModelUpdate(), $sql);
    }

    /**
     * @depends testCacheUpdate
     */
    public function testModelUpdateWithoutWhere()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Languages')->update($this->collection_testModelUpdate(), null, Enjoin::SQL);
        $this->assertEquals("UPDATE `languages` SET `name`='Korean'", $sql);
    }

    /**
     * @depends testMockDataB
     */
    public function testRecordDestroy()
    {
        $it = Enjoin::get('Authors')->create([
            'name' => 'John Dow'
        ]);
        $id = $it->id;
        $it->destroy();
        $check = Enjoin::get('Authors')->findById($id);
        $this->assertNull($check);
    }

    // TODO: test model description getter/setter...
    // TODO: test `hasOne` relation...
    // TODO: test `as` relation...
    // TODO: write several tests for `findOrCreate` using Sequelize.

    /**
     * @param $filename
     * @return array
     */
    private function getDataArray($filename)
    {
        return json_decode($this->getDataFile($filename), true);
    }

    /**
     * @param string $filename
     * @return string
     */
    private function getDataFile($filename)
    {
        return file_get_contents(__DIR__ . '/../data/' . $filename . '.json');
    }

    /**
     * @param string $fnName
     */
    private function handleDebug($fnName)
    {
        if ($fnName === $this->debugFunction) {
            Enjoin::debug(true);
        }
    }

}
