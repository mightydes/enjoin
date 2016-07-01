<?php

use Enjoin\Factory;
use Enjoin\Enjoin;
use Enjoin\Record\Record;
use Enjoin\Exceptions\ValidationException;

class EnjoinTest extends PHPUnit_Framework_TestCase
{

    private $debugFunction = '';

    public function testBootstrap()
    {
        Factory::bootstrap([
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
            'enjoin' => [
                'lang_dir' => 'vendor/caouecs/laravel4-lang'
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
     * SELECT `id`, `name`, `created_at`, `updated_at` FROM `authors` AS `authors` WHERE `authors`.`id` = 1
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
     * SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` LIMIT 1) AS `authors` LEFT OUTER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id`
     * @depends testBootstrap
     */
    public function testFindOneEager()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['include' => Enjoin::get('Books')];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` LIMIT 1) AS `authors` LEFT OUTER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id`",
            $sql
        );

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE ( SELECT `authors_id` FROM `books` AS `books` WHERE (`books`.`authors_id` = `authors`.`id`) LIMIT 1 ) IS NOT NULL LIMIT 1) AS `authors` INNER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id`
     * @depends testBootstrap
     */
    public function testFindOneEagerRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['include' => ['model' => Enjoin::get('Books'), 'required' => true]];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE (SELECT `authors_id` FROM `books` AS `books` WHERE (`authors`.`id` = `books`.`authors_id`) LIMIT 1) IS NOT NULL LIMIT 1) AS `authors` INNER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id`",
            $sql
        );

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at`, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM `authors` AS `authors` LEFT OUTER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` WHERE `authors`.`id` = 1
     * @depends testBootstrap
     */
    public function testFindOneEagerById()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => 1], 'include' => Enjoin::get('Books')];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at`, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM `authors` AS `authors` LEFT OUTER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` WHERE `authors`.`id` = 1",
            $sql
        );

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertEquals([
            1, 'J. R. R. Tolkien', 21, 2, 'The Hobbit: or There and Back Again'
        ], [
            $it->id, $it->name, count($it->books), $it->books[0]->id, $it->books[0]->title
        ]);
    }

    /**
     * SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at`, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM `authors` AS `authors` LEFT OUTER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` WHERE `authors`.`id` = 1
     * @depends testBootstrap
     */
    public function testFindOneEagerByIdRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => 1], 'include' => Enjoin::get('Books')];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at`, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM `authors` AS `authors` LEFT OUTER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` WHERE `authors`.`id` = 1",
            $sql
        );

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at`, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM `authors` AS `authors` LEFT OUTER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` WHERE `authors`.`id` = 1 AND `authors`.`name` IN ('Alice', 'Bob')
     * @depends testBootstrap
     */
    public function testFindOneEagerByIdMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => 1, 'name' => ['Alice', 'Bob']], 'include' => Enjoin::get('Books')];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at`, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM `authors` AS `authors` LEFT OUTER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` WHERE `authors`.`id` = 1 AND `authors`.`name` IN ('Alice', 'Bob')",
            $sql
        );

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE `authors`.`id` IN (1, 2, 3) AND `authors`.`name` IN ('Alice', 'Bob') LIMIT 1) AS `authors` LEFT OUTER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id`
     * @depends testBootstrap
     */
    public function testFindOneEagerMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => [1, 2, 3], 'name' => ['Alice', 'Bob']], 'include' => Enjoin::get('Books')];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE `authors`.`id` IN (1, 2, 3) AND `authors`.`name` IN ('Alice', 'Bob') LIMIT 1) AS `authors` LEFT OUTER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id`",
            $sql
        );

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE `authors`.`id` IN (1, 2, 3) AND `authors`.`name` IN ('Alice', 'Bob') AND ( SELECT `authors_id` FROM `books` AS `books` WHERE (`books`.`authors_id` = `authors`.`id`) LIMIT 1 ) IS NOT NULL LIMIT 1) AS `authors` INNER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id`
     * @depends testBootstrap
     */
    public function testFindOneEagerMeanRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => [1, 2, 3], 'name' => ['Alice', 'Bob']], 'include' => ['model' => Enjoin::get('Books'), 'required' => true]];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`authors_id` AS `books.authors_id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books`.`created_at` AS `books.created_at`, `books`.`updated_at` AS `books.updated_at` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE `authors`.`id` IN (1, 2, 3) AND `authors`.`name` IN ('Alice', 'Bob') AND (SELECT `authors_id` FROM `books` AS `books` WHERE (`authors`.`id` = `books`.`authors_id`) LIMIT 1) IS NOT NULL LIMIT 1) AS `authors` INNER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id`",
            $sql
        );

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` LEFT OUTER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` LIMIT 1
     * @depends testBootstrap
     */
    public function testFindOneEagerReversed()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['include' => Enjoin::get('Authors')];
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` LEFT OUTER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` LIMIT 1",
            $sql
        );

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` INNER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` LIMIT 1
     * @depends testBootstrap
     */
    public function testFindOneEagerReversedRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['include' => ['model' => Enjoin::get('Authors'), 'required' => true]];
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` INNER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` LIMIT 1",
            $sql
        );

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` LEFT OUTER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` WHERE `books`.`id` = 1
     * @depends testBootstrap
     */
    public function testFindOneEagerReversedById()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => 1], 'include' => Enjoin::get('Authors')];
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` LEFT OUTER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` WHERE `books`.`id` = 1",
            $sql
        );

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` INNER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` WHERE `books`.`id` = 1
     * @depends testBootstrap
     */
    public function testFindOneEagerReversedByIdRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => 1], 'include' => ['model' => Enjoin::get('Authors'), 'required' => true]];
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` INNER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` WHERE `books`.`id` = 1",
            $sql
        );

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` LEFT OUTER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` WHERE `books`.`id` IN (1, 2, 3) AND `books`.`title` IN ('Alice', 'Bob') LIMIT 1
     * @depends testBootstrap
     */
    public function testFindOneEagerReversedByIdMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => [1, 2, 3], 'title' => ['Alice', 'Bob']], 'include' => Enjoin::get('Authors')];
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` LEFT OUTER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` WHERE `books`.`id` IN (1, 2, 3) AND `books`.`title` IN ('Alice', 'Bob') LIMIT 1",
            $sql
        );

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` LEFT OUTER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` WHERE (`books`.`id` IN (1, 2, 3) AND `books`.`id` >= 2 AND `books`.`id` < 10) AND `books`.`title` IN ('Alice', 'Bob') LIMIT 1
     * @depends testBootstrap
     */
    public function testFindOneEagerReversedMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => ['in' => [1, 2, 3], 'gte' => 2, 'lt' => 10], 'title' => ['Alice', 'Bob']], 'include' => Enjoin::get('Authors')];
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` LEFT OUTER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` WHERE `books`.`id` IN (1, 2, 3) AND `books`.`id` >= 2 AND `books`.`id` < 10 AND `books`.`title` IN ('Alice', 'Bob') LIMIT 1",
            $sql
        );

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` INNER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` WHERE (`books`.`id` IN (1, 2, 3) AND `books`.`id` >= 2 AND `books`.`id` < 10) AND `books`.`title` IN ('Alice', 'Bob') LIMIT 1
     * @depends testBootstrap
     */
    public function testFindOneEagerReversedMeanRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => ['in' => [1, 2, 3], 'gte' => 2, 'lt' => 10], 'title' => ['Alice', 'Bob']], 'include' => ['model' => Enjoin::get('Authors'), 'required' => true]];
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` INNER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` WHERE `books`.`id` IN (1, 2, 3) AND `books`.`id` >= 2 AND `books`.`id` < 10 AND `books`.`title` IN ('Alice', 'Bob') LIMIT 1",
            $sql
        );

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * SELECT `id`, `name`, `created_at`, `updated_at` FROM `authors` AS `authors` WHERE (`authors`.`id` IN (1, 2, 3) AND `authors`.`id` >= 2 AND `authors`.`id` < 10) AND (`authors`.`name` NOT IN ('Alice', 'Bob') AND `authors`.`name` IS NOT NULL) LIMIT 1
     * @depends testBootstrap
     */
    public function testFindOneComplex()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => ['in' => [1, 2, 3], 'gte' => 2, 'lt' => 10], 'name' => ['notIn' => ['Alice', 'Bob'], 'ne' => null]]];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `id`, `name`, `created_at`, `updated_at` FROM `authors` AS `authors` WHERE `authors`.`id` IN (1, 2, 3) AND `authors`.`id` >= 2 AND `authors`.`id` < 10 AND `authors`.`name` NOT IN ('Alice', 'Bob') AND `authors`.`name` IS NOT NULL LIMIT 1",
            $sql
        );

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertInstanceOf('Enjoin\Record\Record', $it);
    }

    /**
     * SELECT `id`, `name`, `created_at`, `updated_at` FROM `authors` AS `authors` WHERE ((`authors`.`id` = 1 AND `authors`.`name` IS NOT NULL AND (`authors`.`id` = 4 OR `authors`.`id` = 5)) OR ((`authors`.`name` LIKE 'Stephen' AND `authors`.`id` IN (22, 33)) OR (`authors`.`id` NOT IN (1, 2, 3) AND `authors`.`name` NOT LIKE 'Tolkien'))) LIMIT 1
     * @depends testBootstrap
     */
    public function testFindOneAndOr()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['or' => [['and' => [['id' => 1], ['name' => ['ne' => null]], ['or' => [['id' => 4], ['id' => 5]]]]], ['or' => [['and' => [['name' => ['like' => 'Stephen']], ['id' => [22, 33]]]], ['and' => [['id' => ['notIn' => [1, 2, 3]]], ['name' => ['notLike' => 'Tolkien']]]]]]]]];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `id`, `name`, `created_at`, `updated_at` FROM `authors` AS `authors` WHERE ((`authors`.`id` = 1 AND `authors`.`name` IS NOT NULL AND (`authors`.`id` = 4 OR `authors`.`id` = 5)) OR ((`authors`.`name` LIKE 'Stephen' AND `authors`.`id` IN (22, 33)) OR (`authors`.`id` NOT IN (1, 2, 3) AND `authors`.`name` NOT LIKE 'Tolkien'))) LIMIT 1",
            $sql
        );

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
     * SELECT `books`.*, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at`, `reviews`.`id` AS `reviews.id`, `reviews`.`books_id` AS `reviews.books_id`, `reviews`.`resource` AS `reviews.resource`, `reviews`.`content` AS `reviews.content`, `reviews`.`created_at` AS `reviews.created_at`, `reviews`.`updated_at` AS `reviews.updated_at`, `publishers_books`.`id` AS `publishers_books.id`, `publishers_books`.`publishers_id` AS `publishers_books.publishers_id`, `publishers_books`.`books_id` AS `publishers_books.books_id`, `publishers_books`.`year` AS `publishers_books.year`, `publishers_books`.`pressrun` AS `publishers_books.pressrun`, `publishers_books`.`mistakes` AS `publishers_books.mistakes` FROM (SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at` FROM `books` AS `books` LIMIT 1) AS `books` LEFT OUTER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` LEFT OUTER JOIN `reviews` AS `reviews` ON `books`.`id` = `reviews`.`books_id` LEFT OUTER JOIN `publishers_books` AS `publishers_books` ON `books`.`id` = `publishers_books`.`books_id`
     * @depends testMockDataA
     */
    public function testFindOneEagerMulti()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['include' => [Enjoin::get('Authors'), Enjoin::get('Reviews'), Enjoin::get('PublishersBooks')]];
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `books`.*, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at`, `reviews`.`id` AS `reviews.id`, `reviews`.`books_id` AS `reviews.books_id`, `reviews`.`resource` AS `reviews.resource`, `reviews`.`content` AS `reviews.content`, `reviews`.`created_at` AS `reviews.created_at`, `reviews`.`updated_at` AS `reviews.updated_at`, `publishers_books`.`id` AS `publishers_books.id`, `publishers_books`.`publishers_id` AS `publishers_books.publishers_id`, `publishers_books`.`books_id` AS `publishers_books.books_id`, `publishers_books`.`year` AS `publishers_books.year`, `publishers_books`.`pressrun` AS `publishers_books.pressrun`, `publishers_books`.`mistakes` AS `publishers_books.mistakes` FROM (SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at` FROM `books` AS `books` LIMIT 1) AS `books` LEFT OUTER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` LEFT OUTER JOIN `reviews` AS `reviews` ON `books`.`id` = `reviews`.`books_id` LEFT OUTER JOIN `publishers_books` AS `publishers_books` ON `books`.`id` = `publishers_books`.`books_id`",
            $sql
        );

        $it = Enjoin::get('Books')->findOne($params);
        $this->assertEquals(
            [2, 'Nineteen Eighty Four', 'G. Orwell', [], []],
            [$it->authors_id, $it->title, $it->author->name, $it->reviews, $it->publishersBooks]
        );
    }

    /**
     * SELECT `books`.*, `reviews`.`id` AS `reviews.id`, `reviews`.`books_id` AS `reviews.books_id`, `reviews`.`resource` AS `reviews.resource`, `reviews`.`content` AS `reviews.content`, `reviews`.`created_at` AS `reviews.created_at`, `reviews`.`updated_at` AS `reviews.updated_at`, `publishers_books`.`id` AS `publishers_books.id`, `publishers_books`.`publishers_id` AS `publishers_books.publishers_id`, `publishers_books`.`books_id` AS `publishers_books.books_id`, `publishers_books`.`year` AS `publishers_books.year`, `publishers_books`.`pressrun` AS `publishers_books.pressrun`, `publishers_books`.`mistakes` AS `publishers_books.mistakes` FROM (SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` INNER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` WHERE ( SELECT `books_id` FROM `reviews` AS `reviews` WHERE (`reviews`.`books_id` = `books`.`id`) LIMIT 1 ) IS NOT NULL LIMIT 1) AS `books` INNER JOIN `reviews` AS `reviews` ON `books`.`id` = `reviews`.`books_id` LEFT OUTER JOIN `publishers_books` AS `publishers_books` ON `books`.`id` = `publishers_books`.`books_id`
     * @depends testMockDataA
     */
    public function testFindOneEagerMultiRequired()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['include' => [['model' => Enjoin::get('Authors'), 'required' => true], ['model' => Enjoin::get('Reviews'), 'required' => true], Enjoin::get('PublishersBooks')]];
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `books`.*, `reviews`.`id` AS `reviews.id`, `reviews`.`books_id` AS `reviews.books_id`, `reviews`.`resource` AS `reviews.resource`, `reviews`.`content` AS `reviews.content`, `reviews`.`created_at` AS `reviews.created_at`, `reviews`.`updated_at` AS `reviews.updated_at`, `publishers_books`.`id` AS `publishers_books.id`, `publishers_books`.`publishers_id` AS `publishers_books.publishers_id`, `publishers_books`.`books_id` AS `publishers_books.books_id`, `publishers_books`.`year` AS `publishers_books.year`, `publishers_books`.`pressrun` AS `publishers_books.pressrun`, `publishers_books`.`mistakes` AS `publishers_books.mistakes` FROM (SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` INNER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` WHERE (SELECT `books_id` FROM `reviews` AS `reviews` WHERE (`books`.`id` = `reviews`.`books_id`) LIMIT 1) IS NOT NULL LIMIT 1) AS `books` INNER JOIN `reviews` AS `reviews` ON `books`.`id` = `reviews`.`books_id` LEFT OUTER JOIN `publishers_books` AS `publishers_books` ON `books`.`id` = `publishers_books`.`books_id`",
            $sql
        );

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
     * SELECT `books`.*, `reviews`.`id` AS `reviews.id`, `reviews`.`books_id` AS `reviews.books_id`, `reviews`.`resource` AS `reviews.resource`, `reviews`.`content` AS `reviews.content`, `reviews`.`created_at` AS `reviews.created_at`, `reviews`.`updated_at` AS `reviews.updated_at`, `publishers_books`.`id` AS `publishers_books.id`, `publishers_books`.`publishers_id` AS `publishers_books.publishers_id`, `publishers_books`.`books_id` AS `publishers_books.books_id`, `publishers_books`.`year` AS `publishers_books.year`, `publishers_books`.`pressrun` AS `publishers_books.pressrun`, `publishers_books`.`mistakes` AS `publishers_books.mistakes` FROM (SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` INNER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` AND `author`.`name` LIKE '%tol%' WHERE ( SELECT `books_id` FROM `reviews` AS `reviews` WHERE (`reviews`.`books_id` = `books`.`id` AND `reviews`.`resource` NOT LIKE 'wiki') LIMIT 1 ) IS NOT NULL AND ( SELECT `books_id` FROM `publishers_books` AS `publishers_books` WHERE (`publishers_books`.`books_id` = `books`.`id` AND `publishers_books`.`pressrun` > 10000) LIMIT 1 ) IS NOT NULL LIMIT 1) AS `books` INNER JOIN `reviews` AS `reviews` ON `books`.`id` = `reviews`.`books_id` AND `reviews`.`resource` NOT LIKE 'wiki' INNER JOIN `publishers_books` AS `publishers_books` ON `books`.`id` = `publishers_books`.`books_id` AND `publishers_books`.`pressrun` > 10000
     * @depends testMockDataA
     */
    public function testFindOneEagerMultiWhere()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['include' => [['model' => Enjoin::get('Authors'), 'where' => ['name' => ['like' => '%tol%']]], ['model' => Enjoin::get('Reviews'), 'where' => ['resource' => ['notLike' => 'wiki']]], ['model' => Enjoin::get('PublishersBooks'), 'where' => ['pressrun' => ['gt' => 10000]]]]];
        $sql = Enjoin::get('Books')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `books`.*, `reviews`.`id` AS `reviews.id`, `reviews`.`books_id` AS `reviews.books_id`, `reviews`.`resource` AS `reviews.resource`, `reviews`.`content` AS `reviews.content`, `reviews`.`created_at` AS `reviews.created_at`, `reviews`.`updated_at` AS `reviews.updated_at`, `publishers_books`.`id` AS `publishers_books.id`, `publishers_books`.`publishers_id` AS `publishers_books.publishers_id`, `publishers_books`.`books_id` AS `publishers_books.books_id`, `publishers_books`.`year` AS `publishers_books.year`, `publishers_books`.`pressrun` AS `publishers_books.pressrun`, `publishers_books`.`mistakes` AS `publishers_books.mistakes` FROM (SELECT `books`.`id`, `books`.`authors_id`, `books`.`title`, `books`.`year`, `books`.`created_at`, `books`.`updated_at`, `author`.`id` AS `author.id`, `author`.`name` AS `author.name`, `author`.`created_at` AS `author.created_at`, `author`.`updated_at` AS `author.updated_at` FROM `books` AS `books` INNER JOIN `authors` AS `author` ON `books`.`authors_id` = `author`.`id` AND `author`.`name` LIKE '%tol%' WHERE (SELECT `books_id` FROM `reviews` AS `reviews` WHERE (`books`.`id` = `reviews`.`books_id` AND `reviews`.`resource` NOT LIKE 'wiki') LIMIT 1) IS NOT NULL AND (SELECT `books_id` FROM `publishers_books` AS `publishers_books` WHERE (`books`.`id` = `publishers_books`.`books_id` AND `publishers_books`.`pressrun` > 10000) LIMIT 1) IS NOT NULL LIMIT 1) AS `books` INNER JOIN `reviews` AS `reviews` ON `books`.`id` = `reviews`.`books_id` AND `reviews`.`resource` NOT LIKE 'wiki' INNER JOIN `publishers_books` AS `publishers_books` ON `books`.`id` = `publishers_books`.`books_id` AND `publishers_books`.`pressrun` > 10000",
            $sql
        );

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
     * SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`year` AS `books.year`, `books`.`title` AS `books.title`, `books.reviews`.`id` AS `books.reviews.id`, `books.reviews`.`books_id` AS `books.reviews.books_id`, `books.reviews`.`resource` AS `books.reviews.resource`, `books.reviews`.`content` AS `books.reviews.content`, `books.reviews`.`created_at` AS `books.reviews.created_at`, `books.reviews`.`updated_at` AS `books.reviews.updated_at`, `books.publishers_books`.`id` AS `books.publishers_books.id`, `books.publishers_books`.`year` AS `books.publishers_books.year`, `books.publishers_books`.`pressrun` AS `books.publishers_books.pressrun`, `books.publishers_books`.`mistakes` AS `books.publishers_books.mistakes` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE ( SELECT `authors_id` FROM `books` AS `books` WHERE (`books`.`authors_id` = `authors`.`id` AND (`books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930))) LIMIT 1 ) IS NOT NULL LIMIT 1) AS `authors` INNER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` AND `books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930) LEFT OUTER JOIN `reviews` AS `books.reviews` ON `books`.`id` = `books.reviews`.`books_id` INNER JOIN `publishers_books` AS `books.publishers_books` ON `books`.`id` = `books.publishers_books`.`books_id` AND ((`books.publishers_books`.`mistakes` != '' AND `books.publishers_books`.`pressrun` >= 5000) OR `books.publishers_books`.`year` = 1855)
     * @depends testMockDataA
     */
    public function testFindOneEagerNested()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['include' => ['model' => Enjoin::get('Books'), 'where' => ['title' => ['notLike' => 'sad'], 'or' => [['year' => ['lt' => 1920]], ['year' => ['gt' => 1930]]]], 'attributes' => ['year', 'title'], 'include' => [Enjoin::get('Reviews'), ['model' => Enjoin::get('PublishersBooks'), 'where' => ['or' => [['and' => [['mistakes' => ['ne' => '']], ['pressrun' => ['gte' => 5000]]]], ['year' => 1855]]], 'attributes' => ['year', 'pressrun', 'mistakes']]]]];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books.reviews`.`id` AS `books.reviews.id`, `books.reviews`.`books_id` AS `books.reviews.books_id`, `books.reviews`.`resource` AS `books.reviews.resource`, `books.reviews`.`content` AS `books.reviews.content`, `books.reviews`.`created_at` AS `books.reviews.created_at`, `books.reviews`.`updated_at` AS `books.reviews.updated_at`, `books.publishers_books`.`id` AS `books.publishers_books.id`, `books.publishers_books`.`year` AS `books.publishers_books.year`, `books.publishers_books`.`pressrun` AS `books.publishers_books.pressrun`, `books.publishers_books`.`mistakes` AS `books.publishers_books.mistakes` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE (SELECT `authors_id` FROM `books` AS `books` WHERE (`authors`.`id` = `books`.`authors_id` AND `books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930)) LIMIT 1) IS NOT NULL LIMIT 1) AS `authors` INNER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` AND `books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930) LEFT OUTER JOIN `reviews` AS `books.reviews` ON `books`.`id` = `books.reviews`.`books_id` INNER JOIN `publishers_books` AS `books.publishers_books` ON `books`.`id` = `books.publishers_books`.`books_id` AND ((`books.publishers_books`.`mistakes` != '' AND `books.publishers_books`.`pressrun` >= 5000) OR `books.publishers_books`.`year` = 1855)",
            $sql
        );

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
     * SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`year` AS `books.year`, `books`.`title` AS `books.title`, `books.reviews`.`id` AS `books.reviews.id`, `books.reviews`.`books_id` AS `books.reviews.books_id`, `books.reviews`.`resource` AS `books.reviews.resource`, `books.reviews`.`content` AS `books.reviews.content`, `books.reviews`.`created_at` AS `books.reviews.created_at`, `books.reviews`.`updated_at` AS `books.reviews.updated_at`, `books.publishers_books`.`id` AS `books.publishers_books.id`, `books.publishers_books`.`year` AS `books.publishers_books.year`, `books.publishers_books`.`pressrun` AS `books.publishers_books.pressrun`, `books.publishers_books`.`mistakes` AS `books.publishers_books.mistakes` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE (`authors`.`id` IN (1, 2, 3) AND `authors`.`id` >= 2 AND `authors`.`id` < 10) AND ( SELECT `authors_id` FROM `books` AS `books` WHERE (`books`.`authors_id` = `authors`.`id` AND (`books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930))) LIMIT 1 ) IS NOT NULL LIMIT 1) AS `authors` INNER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` AND `books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930) LEFT OUTER JOIN `reviews` AS `books.reviews` ON `books`.`id` = `books.reviews`.`books_id` INNER JOIN `publishers_books` AS `books.publishers_books` ON `books`.`id` = `books.publishers_books`.`books_id` AND ((`books.publishers_books`.`mistakes` != '' AND `books.publishers_books`.`pressrun` >= 5000) OR `books.publishers_books`.`year` = 1855)
     * @depends testMockDataA
     */
    public function testFindOneEagerNestedById()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => ['in' => [1, 2, 3], 'gte' => 2, 'lt' => 10]], 'include' => ['model' => Enjoin::get('Books'), 'where' => ['title' => ['notLike' => 'sad'], 'or' => [['year' => ['lt' => 1920]], ['year' => ['gt' => 1930]]]], 'attributes' => ['year', 'title'], 'include' => [Enjoin::get('Reviews'), ['model' => Enjoin::get('PublishersBooks'), 'where' => ['or' => [['and' => [['mistakes' => ['ne' => '']], ['pressrun' => ['gte' => 5000]]]], ['year' => 1855]]], 'attributes' => ['year', 'pressrun', 'mistakes']]]]];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books.reviews`.`id` AS `books.reviews.id`, `books.reviews`.`books_id` AS `books.reviews.books_id`, `books.reviews`.`resource` AS `books.reviews.resource`, `books.reviews`.`content` AS `books.reviews.content`, `books.reviews`.`created_at` AS `books.reviews.created_at`, `books.reviews`.`updated_at` AS `books.reviews.updated_at`, `books.publishers_books`.`id` AS `books.publishers_books.id`, `books.publishers_books`.`year` AS `books.publishers_books.year`, `books.publishers_books`.`pressrun` AS `books.publishers_books.pressrun`, `books.publishers_books`.`mistakes` AS `books.publishers_books.mistakes` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE `authors`.`id` IN (1, 2, 3) AND `authors`.`id` >= 2 AND `authors`.`id` < 10 AND (SELECT `authors_id` FROM `books` AS `books` WHERE (`authors`.`id` = `books`.`authors_id` AND `books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930)) LIMIT 1) IS NOT NULL LIMIT 1) AS `authors` INNER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` AND `books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930) LEFT OUTER JOIN `reviews` AS `books.reviews` ON `books`.`id` = `books.reviews`.`books_id` INNER JOIN `publishers_books` AS `books.publishers_books` ON `books`.`id` = `books.publishers_books`.`books_id` AND ((`books.publishers_books`.`mistakes` != '' AND `books.publishers_books`.`pressrun` >= 5000) OR `books.publishers_books`.`year` = 1855)",
            $sql
        );

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`year` AS `books.year`, `books`.`title` AS `books.title`, `books.reviews`.`id` AS `books.reviews.id`, `books.reviews`.`books_id` AS `books.reviews.books_id`, `books.reviews`.`resource` AS `books.reviews.resource`, `books.reviews`.`content` AS `books.reviews.content`, `books.reviews`.`created_at` AS `books.reviews.created_at`, `books.reviews`.`updated_at` AS `books.reviews.updated_at`, `books.publishers_books`.`id` AS `books.publishers_books.id`, `books.publishers_books`.`year` AS `books.publishers_books.year`, `books.publishers_books`.`pressrun` AS `books.publishers_books.pressrun`, `books.publishers_books`.`mistakes` AS `books.publishers_books.mistakes` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE (`authors`.`id` >= 0 AND `authors`.`id` < 10) AND (`authors`.`name` != 'Bob' OR `authors`.`name` != 'Alice') AND ( SELECT `authors_id` FROM `books` AS `books` WHERE (`books`.`authors_id` = `authors`.`id` AND (`books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930))) LIMIT 1 ) IS NOT NULL LIMIT 1) AS `authors` INNER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` AND `books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930) LEFT OUTER JOIN `reviews` AS `books.reviews` ON `books`.`id` = `books.reviews`.`books_id` INNER JOIN `publishers_books` AS `books.publishers_books` ON `books`.`id` = `books.publishers_books`.`books_id` AND ((`books.publishers_books`.`mistakes` != '' AND `books.publishers_books`.`pressrun` >= 5000) OR `books.publishers_books`.`year` = 1855)
     * @depends testMockDataA
     */
    public function testFindOneEagerNestedMean()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => ['gte' => 0, 'lt' => 10], 'name' => ['or' => [['ne' => 'Bob'], ['ne' => 'Alice']]]], 'include' => ['model' => Enjoin::get('Books'), 'where' => ['title' => ['notLike' => 'sad'], 'or' => [['year' => ['lt' => 1920]], ['year' => ['gt' => 1930]]]], 'attributes' => ['year', 'title'], 'include' => [Enjoin::get('Reviews'), ['model' => Enjoin::get('PublishersBooks'), 'where' => ['or' => [['and' => [['mistakes' => ['ne' => '']], ['pressrun' => ['gte' => 5000]]]], ['year' => 1855]]], 'attributes' => ['year', 'pressrun', 'mistakes']]]]];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books.reviews`.`id` AS `books.reviews.id`, `books.reviews`.`books_id` AS `books.reviews.books_id`, `books.reviews`.`resource` AS `books.reviews.resource`, `books.reviews`.`content` AS `books.reviews.content`, `books.reviews`.`created_at` AS `books.reviews.created_at`, `books.reviews`.`updated_at` AS `books.reviews.updated_at`, `books.publishers_books`.`id` AS `books.publishers_books.id`, `books.publishers_books`.`year` AS `books.publishers_books.year`, `books.publishers_books`.`pressrun` AS `books.publishers_books.pressrun`, `books.publishers_books`.`mistakes` AS `books.publishers_books.mistakes` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE `authors`.`id` >= 0 AND `authors`.`id` < 10 AND (`authors`.`name` != 'Bob' OR `authors`.`name` != 'Alice') AND (SELECT `authors_id` FROM `books` AS `books` WHERE (`authors`.`id` = `books`.`authors_id` AND `books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930)) LIMIT 1) IS NOT NULL LIMIT 1) AS `authors` INNER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` AND `books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930) LEFT OUTER JOIN `reviews` AS `books.reviews` ON `books`.`id` = `books.reviews`.`books_id` INNER JOIN `publishers_books` AS `books.publishers_books` ON `books`.`id` = `books.publishers_books`.`books_id` AND ((`books.publishers_books`.`mistakes` != '' AND `books.publishers_books`.`pressrun` >= 5000) OR `books.publishers_books`.`year` = 1855)",
            $sql
        );

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
     * SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`year` AS `books.year`, `books`.`title` AS `books.title`, `books.reviews`.`id` AS `books.reviews.id`, `books.reviews`.`books_id` AS `books.reviews.books_id`, `books.reviews`.`resource` AS `books.reviews.resource`, `books.reviews`.`content` AS `books.reviews.content`, `books.reviews`.`created_at` AS `books.reviews.created_at`, `books.reviews`.`updated_at` AS `books.reviews.updated_at`, `books.publishers_books`.`id` AS `books.publishers_books.id`, `books.publishers_books`.`year` AS `books.publishers_books.year`, `books.publishers_books`.`pressrun` AS `books.publishers_books.pressrun`, `books.publishers_books`.`mistakes` AS `books.publishers_books.mistakes`, `books.publishers_books.shippeds`.`id` AS `books.publishers_books.shippeds.id`, `books.publishers_books.shippeds`.`publishers_books_id` AS `books.publishers_books.shippeds.publishers_books_id`, `books.publishers_books.shippeds`.`destination` AS `books.publishers_books.shippeds.destination`, `books.publishers_books.shippeds`.`quantity` AS `books.publishers_books.shippeds.quantity`, `books.publishers_books.shippeds`.`sent_at` AS `books.publishers_books.shippeds.sent_at`, `books.publishers_books.shippeds`.`created_at` AS `books.publishers_books.shippeds.created_at`, `books.publishers_books.shippeds`.`updated_at` AS `books.publishers_books.shippeds.updated_at`, `books.publishers_books.preorders`.`id` AS `books.publishers_books.preorders.id`, `books.publishers_books.preorders`.`publishers_books_id` AS `books.publishers_books.preorders.publishers_books_id`, `books.publishers_books.preorders`.`person` AS `books.publishers_books.preorders.person`, `books.publishers_books.preorders`.`quantity` AS `books.publishers_books.preorders.quantity`, `books.publishers_books.preorders`.`created_at` AS `books.publishers_books.preorders.created_at`, `books.publishers_books.preorders`.`updated_at` AS `books.publishers_books.preorders.updated_at` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE (`authors`.`id` >= 0 AND `authors`.`id` < 10) AND (`authors`.`name` != 'Bob' OR `authors`.`name` != 'Alice') AND ( SELECT `authors_id` FROM `books` AS `books` WHERE (`books`.`authors_id` = `authors`.`id` AND (`books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930))) LIMIT 1 ) IS NOT NULL LIMIT 1) AS `authors` INNER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` AND `books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930) LEFT OUTER JOIN `reviews` AS `books.reviews` ON `books`.`id` = `books.reviews`.`books_id` INNER JOIN `publishers_books` AS `books.publishers_books` ON `books`.`id` = `books.publishers_books`.`books_id` AND ((`books.publishers_books`.`mistakes` != '' AND `books.publishers_books`.`pressrun` >= 5000) OR `books.publishers_books`.`year` = 1855) INNER JOIN `shipped` AS `books.publishers_books.shippeds` ON `books.publishers_books`.`id` = `books.publishers_books.shippeds`.`publishers_books_id` AND `books.publishers_books.shippeds`.`quantity` > 300 LEFT OUTER JOIN `preorders` AS `books.publishers_books.preorders` ON `books.publishers_books`.`id` = `books.publishers_books.preorders`.`publishers_books_id` AND `books.publishers_books.preorders`.`quantity` < 155000
     * @depends testMockDataA
     */
    public function testFindOneEagerNestedDeep()
    {
        $this->handleDebug(__FUNCTION__);
        $params = ['where' => ['id' => ['gte' => 0, 'lt' => 10], 'name' => ['or' => [['ne' => 'Bob'], ['ne' => 'Alice']]]], 'include' => ['model' => Enjoin::get('Books'), 'where' => ['title' => ['notLike' => 'sad'], 'or' => [['year' => ['lt' => 1920]], ['year' => ['gt' => 1930]]]], 'attributes' => ['year', 'title'], 'include' => [Enjoin::get('Reviews'), ['model' => Enjoin::get('PublishersBooks'), 'where' => ['or' => [['and' => [['mistakes' => ['ne' => '']], ['pressrun' => ['gte' => 5000]]]], ['year' => 1855]]], 'attributes' => ['year', 'pressrun', 'mistakes'], 'include' => [['model' => Enjoin::get('Shipped'), 'where' => ['quantity' => ['gt' => 300]]], ['model' => Enjoin::get('Preorders'), 'where' => ['quantity' => ['lt' => 155000]], 'required' => false]]]]]];
        $sql = Enjoin::get('Authors')->findOne($params, Enjoin::SQL);
        $this->assertEquals(
            "SELECT `authors`.*, `books`.`id` AS `books.id`, `books`.`title` AS `books.title`, `books`.`year` AS `books.year`, `books.reviews`.`id` AS `books.reviews.id`, `books.reviews`.`books_id` AS `books.reviews.books_id`, `books.reviews`.`resource` AS `books.reviews.resource`, `books.reviews`.`content` AS `books.reviews.content`, `books.reviews`.`created_at` AS `books.reviews.created_at`, `books.reviews`.`updated_at` AS `books.reviews.updated_at`, `books.publishers_books`.`id` AS `books.publishers_books.id`, `books.publishers_books`.`year` AS `books.publishers_books.year`, `books.publishers_books`.`pressrun` AS `books.publishers_books.pressrun`, `books.publishers_books`.`mistakes` AS `books.publishers_books.mistakes`, `books.publishers_books.shippeds`.`id` AS `books.publishers_books.shippeds.id`, `books.publishers_books.shippeds`.`publishers_books_id` AS `books.publishers_books.shippeds.publishers_books_id`, `books.publishers_books.shippeds`.`destination` AS `books.publishers_books.shippeds.destination`, `books.publishers_books.shippeds`.`quantity` AS `books.publishers_books.shippeds.quantity`, `books.publishers_books.shippeds`.`sent_at` AS `books.publishers_books.shippeds.sent_at`, `books.publishers_books.shippeds`.`created_at` AS `books.publishers_books.shippeds.created_at`, `books.publishers_books.shippeds`.`updated_at` AS `books.publishers_books.shippeds.updated_at`, `books.publishers_books.preorders`.`id` AS `books.publishers_books.preorders.id`, `books.publishers_books.preorders`.`publishers_books_id` AS `books.publishers_books.preorders.publishers_books_id`, `books.publishers_books.preorders`.`person` AS `books.publishers_books.preorders.person`, `books.publishers_books.preorders`.`quantity` AS `books.publishers_books.preorders.quantity`, `books.publishers_books.preorders`.`created_at` AS `books.publishers_books.preorders.created_at`, `books.publishers_books.preorders`.`updated_at` AS `books.publishers_books.preorders.updated_at` FROM (SELECT `authors`.`id`, `authors`.`name`, `authors`.`created_at`, `authors`.`updated_at` FROM `authors` AS `authors` WHERE `authors`.`id` >= 0 AND `authors`.`id` < 10 AND (`authors`.`name` != 'Bob' OR `authors`.`name` != 'Alice') AND (SELECT `authors_id` FROM `books` AS `books` WHERE (`authors`.`id` = `books`.`authors_id` AND `books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930)) LIMIT 1) IS NOT NULL LIMIT 1) AS `authors` INNER JOIN `books` AS `books` ON `authors`.`id` = `books`.`authors_id` AND `books`.`title` NOT LIKE 'sad' AND (`books`.`year` < 1920 OR `books`.`year` > 1930) LEFT OUTER JOIN `reviews` AS `books.reviews` ON `books`.`id` = `books.reviews`.`books_id` INNER JOIN `publishers_books` AS `books.publishers_books` ON `books`.`id` = `books.publishers_books`.`books_id` AND ((`books.publishers_books`.`mistakes` != '' AND `books.publishers_books`.`pressrun` >= 5000) OR `books.publishers_books`.`year` = 1855) INNER JOIN `shipped` AS `books.publishers_books.shippeds` ON `books.publishers_books`.`id` = `books.publishers_books.shippeds`.`publishers_books_id` AND `books.publishers_books.shippeds`.`quantity` > 300 LEFT OUTER JOIN `preorders` AS `books.publishers_books.preorders` ON `books.publishers_books`.`id` = `books.publishers_books.preorders`.`publishers_books_id` AND `books.publishers_books.preorders`.`quantity` < 155000",
            $sql
        );

        $it = Enjoin::get('Authors')->findOne($params);
        $this->assertTrue(is_null($it));
    }

    /**
     * @depends testBootstrap
     */
    public function testExpanseModel()
    {
        $this->assertEquals('OK', Enjoin::get('Authors')->ping());
    }

    /**
     * SELECT `id`, `name`, `created_at`, `updated_at` FROM `authors` AS `authors`
     * @depends testMockDataA
     */
    public function testFindAll()
    {
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

    // TODO: test model description getter/setter...
    // TODO: test `hasOne` relation...
    // TODO: test `as` relation...

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
