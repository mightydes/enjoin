<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'CompareTrait.php';
require_once 'CompareQueries.php';

use Enjoin\Factory;
use Enjoin\Enjoin;
use Enjoin\Extras;
use Enjoin\Record\Record;
use Enjoin\Exceptions\ValidationException;
use Carbon\Carbon;
use Dotenv\Dotenv;

class EnjoinTest extends PHPUnit_Framework_TestCase
{

    use CompareTrait;

    private $debugFunction = 'testDefinitionBooleanType';

    public function testBootstrap()
    {
        (new Dotenv(__DIR__ . '/../../'))->overload();
        $this->handleDebug(__FUNCTION__);
        Factory::bootstrap([
            'database' => [
                'default' => 'test',
                'connections' => [
                    'test' => [
                        'driver' => $this->getDriver(),
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
                'default' => getenv('ENJ_CACHE'),
                'stores' => [
                    'redis' => [
                        'driver' => 'redis',
                        'connection' => 'default'
                    ],
                    'memcached' => [
                        'driver' => 'memcached',
                        'servers' => [
                            ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100]
                        ],
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
        $this->handleDebug(__FUNCTION__);
        Enjoin::get('Authors');
        $this->assertArrayHasKey('\Models\Authors', Factory::getInstance()->models);
    }

    /**
     * @depends testBootstrap
     * @return Record
     */
    public function testModelBuild()
    {
        $this->handleDebug(__FUNCTION__);
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
    public function testNewRecordSave(Record $it)
    {
        $this->handleDebug(__FUNCTION__);
        $it->save();
        $this->assertEquals(1, $it->id);
    }

    /**
     * @depends testBootstrap
     * @return Record
     */
    public function testNewRecordNestedSave()
    {
        $this->handleDebug(__FUNCTION__);
        $it = Enjoin::get('Authors')->build([
            'name' => 'George Orwell',
            'book' => Enjoin::get('Books')->build([
                'title' => 'Nineteen Eighty Four',
                'year' => 1942
            ])
        ]);
        $it->save();
        $it->book->save();
        $this->assertEquals([2, 1], [$it->id, $it->book->id]);
        return $it;
    }

    /**
     * @depends testNewRecordNestedSave
     * @param Record $it
     */
    public function testRecordSave(Record $it)
    {
        $this->handleDebug(__FUNCTION__);
        $authorName = 'G. Orwell';
        $bookAuthorId = 2;

        $it->name = $authorName;
        $it->book->authors_id = $bookAuthorId;
        $it->save();
        $it->book->save();
        $this->assertEquals([$authorName, $bookAuthorId], [$it->name, $it->book->authors_id]);
    }

    /**
     * @depends testNewRecordNestedSave
     * @param Record $it
     */
    public function testRecordValidation(Record $it)
    {
        $this->handleDebug(__FUNCTION__);
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
        $this->handleDebug(__FUNCTION__);
        $it = Enjoin::get('Publishers')->create($this->getCompareCollection(__FUNCTION__));
        $this->assertEquals(1, $it->id);
        return $it;
    }

    /**
     * @depends testBootstrap
     */
    public function testModelCreateEmpty()
    {
        $this->handleDebug(__FUNCTION__);
        $assert = [];
        $log = Enjoin::logify(function () use (&$assert) {
            $assert [] = Enjoin::get('Languages')->create()->id;
            $assert [] = Enjoin::get('Languages')->create([])->id;
        });
        $assert [] = $log[0];
        $this->assertEquals(
            [1, 2, $this->getCompareSql(__FUNCTION__)],
            $assert
        );
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
    public function testModelFindById()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findById(1, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);
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
    public function testModelBulkCreateValidation(Record $author)
    {
        $bulk = [[
            'title' => 'testModelBulkCreateValidation',
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
     * @depends testModelBulkCreateValidation
     * @param Record $author
     */
    public function testModelBulkCreate(Record $author)
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
    public function testModelFindOneILike()
    {
        $fnName = __FUNCTION__;
        $this->ifPostgreSql(function () use ($fnName) {
            $this->handleDebug($fnName);
            $params = $this->getCompareParams($fnName);
            $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
            $this->assertEquals($this->getCompareSql($fnName), $sql);
        });
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneNotILike()
    {
        $fnName = __FUNCTION__;
        $this->ifPostgreSql(function () use ($fnName) {
            $this->handleDebug($fnName);
            $params = $this->getCompareParams($fnName);
            $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
            $this->assertEquals($this->getCompareSql($fnName), $sql);
        });
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEager()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEagerRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->getCompareSql(__FUNCTION__), $sql));

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEagerById()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

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
    public function testModelFindOneEagerByIdRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEagerByIdMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEagerMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEagerMeanRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->getCompareSql(__FUNCTION__), $sql));

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEagerReversed()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEagerReversedRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEagerReversedById()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEagerReversedByIdRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEagerReversedByIdMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEagerReversedMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneEagerReversedMeanRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneComplex()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * @depends testBootstrap
     */
    public function testModelFindOneAndOr()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

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
    public function testModelFindOneEagerMulti()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertEquals(
            [2, 'Nineteen Eighty Four', 'G. Orwell', [], []],
            [$it->authors_id, $it->title, $it->author->name, $it->reviews, $it->publishersBooks]
        );
    }

    /**
     * @depends testMockDataA
     */
    public function testModelFindOneEagerMultiRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->getCompareSql(__FUNCTION__), $sql));

        $it = Enjoin::get('Books')->findOne($params);
        $review = Extras::findWhere($it->reviews, ['id' => 1]);
        $this->assertEquals([
            1, 1937, 'J. R. R. Tolkien',
            25, 'ac leo pellentesque ultrices mattis odio donec vitae nisi nam',
            5, 5000
        ], [
            $it->authors_id, $it->year, $it->author->name,
            count($it->reviews), $review->resource,
            count($it->publishersBooks), $it->publishersBooks[0]->pressrun
        ]);
    }

    /**
     * @depends testMockDataA
     */
    public function testModelFindOneEagerMultiWhere()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->getCompareSql(__FUNCTION__), $sql));

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
    public function testModelFindOneEagerNested()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->getCompareSql(__FUNCTION__), $sql));

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
    public function testModelFindOneEagerNestedById()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->getCompareSql(__FUNCTION__), $sql));

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testMockDataA
     */
    public function testModelFindOneEagerNestedMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->getCompareSql(__FUNCTION__), $sql));

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
    public function testModelFindOneEagerNestedDeep()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->getCompareSql(__FUNCTION__), $sql));

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @todo: see https://github.com/sequelize/sequelize/issues/3917
     * @depends testMockDataA
     */
    public function testModelFindOneEagerSelfNestedNoSubQuery()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);
    }

    /**
     * @depends testBootstrap
     */
    public function testDefinitionExpanseModel()
    {
        $this->handleDebug(__FUNCTION__);
        $this->assertEquals('OK', Enjoin::get('Authors')->ping());
    }

    /**
     * @depends testMockDataA
     */
    public function testModelFindAll()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findAll(null, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $r = Enjoin::get('Authors')->findAll();
        $this->assertEquals(
            [2, 'J. R. R. Tolkien', 2],
            [count($r), $r[0]->name, $r[1]->id]
        );
    }

    /**
     * @depends testMockDataA
     */
    public function testModelFindAllEmptyList()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->findAll($this->getCompareParams(__FUNCTION__), Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);
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
    public function testModelFindAllEagerOneThenMany()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findAll($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $r = Enjoin::get('Books')->findAll($params);
        $this->assertEquals(
            [22, 1, 2, 12],
            [count($r), $r[0]->id, $r[0]->author->id, count($r[0]->author->articles)]
        );
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindAllEagerOneThenManyMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findAll($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $r = Enjoin::get('Books')->findAll($params);
        $this->assertEquals(
            [1, 1, 2, 12],
            [count($r), $r[0]->id, $r[0]->author->id, count($r[0]->author->articles)]
        );
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindAllEagerOneThenManyMeanOrdered()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findAll($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $r = Enjoin::get('Books')->findAll($params);
        $this->assertEquals(1980, $r[0]->author->articles[0]->year);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindAllEagerOneThenManyMeanGrouped()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Books')->findAll($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $r = Enjoin::get('Books')->findAll($params);
        $book = Extras::findWhere($r, ['id' => 1]);
        $article = Extras::findWhere($book->author->articles, ['id' => 3]);
        $this->assertEquals(1928, $article->year);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindAllEagerNestedDeep()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findAll($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->getCompareSql(__FUNCTION__), $sql));
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindAllEagerNestedDeepLimited()
    {
        $this->handleDebug(__FUNCTION__);
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findAll($params, Enjoin::SQL);
        $this->assertTrue(CompareQueries::isSame($this->getCompareSql(__FUNCTION__), $sql));
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindAllEqArray()
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
    public function testModelCount()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->count(null, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);

        $r = Enjoin::get('Authors')->count();
        $this->assertEquals(2, $r);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelCountConditional()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->count($this->getCompareParams(__FUNCTION__), Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelCountEagerOneThenMany()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->count($this->getCompareParams(__FUNCTION__), Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelCountEagerOneThenManyMean()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->count($this->getCompareParams(__FUNCTION__), Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelCountEagerRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->count($this->getCompareParams(__FUNCTION__), Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelCountEagerRequiredLimited()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->count($this->getCompareParams(__FUNCTION__), Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindAndCountAll()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findAndCountAll(null, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql['count']);

        $r = Enjoin::get('Authors')->findAndCountAll();
        $this->assertEquals(
            [2, 'G. Orwell'],
            [$r['count'], $r['rows'][1]->name]
        );
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindAndCountAllConditional()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->findAndCountAll($this->getCompareParams(__FUNCTION__), Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql['count']);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindAndCountAllEagerOneThenMany()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->findAndCountAll($this->getCompareParams(__FUNCTION__), Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql['count']);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindAndCountAllEagerOneThenManyMean()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Books')->findAndCountAll($this->getCompareParams(__FUNCTION__), Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql['count']);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindAndCountAllEagerRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findAndCountAll($this->getCompareParams(__FUNCTION__), Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql['count']);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindAndCountAllEagerRequiredLimited()
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Authors')->findAndCountAll($this->getCompareParams(__FUNCTION__), Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql['count']);
    }

    /**
     * @depends testMockDataB
     */
    public function testModelFindOrCreate()
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
     * @depends testModelFindOrCreate
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
     * @depends testRecordUpdate
     */
    public function testModelFindCreateFind()
    {
        $this->handleDebug(__FUNCTION__);
        $it = Enjoin::get('Books')->findCreateFind([
            'where' => [
                'title' => 'Keep the Aspidistra Flying',
                'year' => 1936
            ],
            'defaults' => ['authors_id' => 2]
        ]);
        $this->assertEquals(
            [25, 'Keep the Aspidistra Flying', 1936],
            [$it->id, $it->title, $it->year]
        );
    }

    /**
     * @depends testModelFindOrCreate
     */
    public function testCache()
    {
        $this->handleDebug(__FUNCTION__);
        Factory::getCache()->flush();
        $it = Enjoin::get('Books')->findById(1, Enjoin::WITH_CACHE);
        $cache = Enjoin::get('Books')->cache()->getCacheInstance()->get('9125bfc211f5ddbce7352499c9c71973');
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
        $cacheKey = Enjoin::get('Books')->cache()->keyify(['findOne', $params]);
        $cache = Enjoin::get('Books')->cache()->getCacheInstance()->get($cacheKey);
        $this->assertEquals($it, $cache);
        $cache->author->update(['name' => 'George Orwell']);
        $cache = Enjoin::get('Books')->cache()->getCacheInstance()->get($cacheKey);
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
        $params = $this->getCompareParams(__FUNCTION__);
        $sql = Enjoin::get('Authors')->destroy($params, Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);
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
     * @return array
     */
    public function testModelUpdate()
    {
        $this->handleDebug(__FUNCTION__);
        $collection = $this->getCompareCollection(__FUNCTION__);
        $sql = Enjoin::get('Languages')->update($collection, $this->getCompareParams(__FUNCTION__), Enjoin::SQL);
        $this->assertEquals($this->getCompareSql(__FUNCTION__), $sql);
        return [
            'collection' => $collection,
            'sql' => $sql
        ];
    }

    /**
     * @depends testModelUpdate
     * @param array $data
     */
    public function testModelUpdateWithoutWhere(array $data)
    {
        $this->handleDebug(__FUNCTION__);
        $sql = Enjoin::get('Languages')->update($data['collection'], null, Enjoin::SQL);
        $assertSql = explode(' WHERE ', $data['sql'])[0];
        $this->assertEquals($assertSql, $sql);
    }

    /**
     * @depends testMockDataB
     */
    public function testRecordDestroy()
    {
        $this->handleDebug(__FUNCTION__);
        $it = Enjoin::get('Authors')->create([
            'name' => 'John Dow'
        ]);
        $id = $it->id;
        $it->destroy();
        $check = Enjoin::get('Authors')->findById($id);
        $this->assertNull($check);
    }

    /**
     * @depends testMockDataB
     */
    public function testDefinitionBooleanType()
    {
        $this->handleDebug(__FUNCTION__);
        $log = Enjoin::logify(function () {
            $it = Enjoin::get('Pile')->findCreateFind([
                'where' => [
                    'on_state' => true,
                    'date_till' => '2016-12-07 00:00:00'
                ]
            ]);
            $it = Enjoin::get('Pile')->findCreateFind([
                'where' => [
                    'on_state' => false,
                    'date_till' => '2016-12-07 01:00:00'
                ]
            ]);
        });
        if ($this->ifPostgreSql()) {
            $expected = [
                "SELECT \"id\", \"on_state\", \"date_till\", \"created_at\", \"updated_at\" FROM \"pile\" AS \"pile\" WHERE \"pile\".\"on_state\" = 1 AND \"pile\".\"date_till\" = '2016-12-07 00:00:00' LIMIT 1",
                "SELECT \"id\", \"on_state\", \"date_till\", \"created_at\", \"updated_at\" FROM \"pile\" AS \"pile\" WHERE \"pile\".\"on_state\" IS NULL AND \"pile\".\"date_till\" = '2016-12-07 01:00:00' LIMIT 1"
            ];
        } else {
            $expected = [
                "SELECT `id`, `on_state`, `date_till`, `created_at`, `updated_at` FROM `pile` AS `pile` WHERE `pile`.`on_state` = 1 AND `pile`.`date_till` = '2016-12-07 00:00:00' LIMIT 1",
                "SELECT `id`, `on_state`, `date_till`, `created_at`, `updated_at` FROM `pile` AS `pile` WHERE `pile`.`on_state` IS NULL AND `pile`.`date_till` = '2016-12-07 01:00:00' LIMIT 1"
            ];
        }
        $this->assertEquals($expected, [
            $log[0],
            $log[3]
        ]);
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

    /**
     * @return string
     */
    private function getDriver()
    {
        $dialect = getenv('ENJ_DIALECT');
        if ($dialect === 'postgresql') {
            return 'pgsql';
        }
        return $dialect;
    }

    /**
     * @param Closure|null $test
     * @return mixed|bool
     */
    private function ifPostgreSql(Closure $test = null)
    {
        $itIs = getenv('ENJ_DIALECT') === 'postgresql';
        if (is_callable($test)) {
            if ($itIs) {
                return $test();
            }
        } else {
            return $itIs;
        }
    }

}
