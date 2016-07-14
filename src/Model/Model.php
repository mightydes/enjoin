<?php

namespace Enjoin\Model;

use Doctrine\Common\Inflector\Inflector;
use Illuminate\Database\Capsule\Manager as Capsule;
use Enjoin\Factory;
use Enjoin\Record\Record;
use Enjoin\Record\Records;
use Enjoin\Builder\Find;
use Enjoin\Enjoin;
use PdoDebugger;

class Model
{

    /**
     * Model definition instance.
     * @var Definition
     */
    public $Definition;

    public $unique;

    /**
     * @param Definition $Definition
     */
    public function __construct(Definition $Definition)
    {
        if (!property_exists($Definition, 'table') || !$Definition->table) {
            # Define table name from model name
            # if not performed manually:
            $arr = explode('\\', get_class($Definition));
            $Definition->table = Inflector::tableize(end($arr));
        }
        $this->Definition = $Definition;
        $this->unique = get_class($Definition);
//        $this->CC = new CacheControl($this);
    }

//    /**
//     * @return \Illuminate\Database\Query\Builder
//     */
//    public function connect()
//    {
//        $key = $this->Definition->connection;
//        $key ?: $key = Factory::getConfig()['default'];
//        return Capsule::connection($key)->table($this->Definition->table);
//    }

    /**
     * @return \Illuminate\Database\Connection
     */
    public function connection()
    {
        $key = $this->Definition->connection;
        $key ?: $key = Factory::getConfig()['default'];
        return Capsule::connection($key);
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function queryBuilder()
    {
        return $this->connection()->table($this->Definition->table);
    }

    /**
     * @param array|object|null $collection
     * @param array|null $attributes
     * @return Record
     */
    public function build($collection = null, array $attributes = null)
    {
        $collection ?: $collection = [];
        $recordClass = $this->Definition->expanseRecord ?: Record::class;
        $Record = new $recordClass($this);
        foreach ($collection as $k => $v) {
            if (!$attributes || in_array($k, $attributes)) {
                $Record->$k = $v;
            }
        }
        return $Record;
    }

    /**
     * @param array|object|null $collection
     * @param array|null $attributes
     * @return Record
     */
    public function create($collection = null, array $attributes = null)
    {
//        $this->CC->flush();
        return $this->build($collection, $attributes)->save();
    }

    /**
     * @param array $collections
     * @return bool
     */
    public function bulkCreate(array $collections)
    {
//        $this->CC->flush();

        $bulk = [];
        $Setters = Factory::getSetters();
        $defAttributes = $this->Definition->getAttributes();
        foreach ($collections as $record) {
            $volume = [];
            $skip = [];
            # Perform timestamps:
            if ($this->isTimestamps()) {
                # Created at:
                $createdAtAttr = $this->getCreatedAtAttr();
                $volume[$createdAtAttr] = $Setters->getCreatedAt(isset($record[$createdAtAttr]) ? $record[$createdAtAttr] : null);
                $skip [] = $createdAtAttr;
                # Updated at:
                $updatedAtAttr = $this->getUpdatedAtAttr();
                $volume[$updatedAtAttr] = $Setters->getUpdatedAt();
                $skip [] = $updatedAtAttr;
            }
            # Perform setters:
            $validate = [];
            foreach (array_diff(array_keys($record), $skip) as $attr) {
                if (array_key_exists($attr, $defAttributes)) {
                    $volume[$attr] = $Setters->perform($record, $defAttributes[$attr], $attr);
                    if (isset($defAttributes[$attr]['validate'])) {
                        $validate [] = [$attr, $volume[$attr], $defAttributes[$attr]['validate']];
                    }
                }
            }
            !$validate ?: $Setters->validate($validate);
            $bulk [] = $volume;
        }
        return $this->queryBuilder()->insert($bulk);
    }

    /**
     * @param array $params
     * @param int $flags
     * @return Record|null|array
     */
    public function findOne(array $params, $flags = 0)
    {
        // TODO: cache...
        // TODO: enable call without params.
        unset($params['offset']);
        $params['limit'] = 1;
        $Find = new Find($this, $params);
        list($query, $place) = $Find->getPrepared();
        if ($flags & Enjoin::SQL) {
            return PdoDebugger::show($query, $place);
        }
        $rows = $this->connection()->select($query, $place);
        if ($rows) {
            $Records = new Records($Find->Tree);
            $out = $Records->handleRows($rows)[0];
            return $out;
        }
        return null;
    }

    /**
     * @param int $id
     * @param int $flags
     * @return array|Record|null
     */
    public function findById($id, $flags = 0)
    {
        return $this->findOne(['where' => ['id' => $id]], $flags);
    }

    /**
     * @deprecated Use `findById()` and `findOne()` instead.
     * @param array|integer $params
     * @param int $flags
     * @return Record|null
     */
    public function find($params, $flags = 0)
    {
        # Handle find by id (ie `->find(1)`):
        if (!is_array($params)) {
            $params = ['where' => ['id' => $params]];
        }
        return $this->findOne($params, $flags);
    }

    /**
     * @param array|null $params
     * @param int $flags
     * @return array|mixed
     */
    public function findAll(array $params = null, $flags = 0)
    {
        // TODO: cache...
        $params ?: $params = [];
        $Find = new Find($this, $params);
        list($query, $place) = $Find->getPrepared();
        if ($flags & Enjoin::SQL) {
            return PdoDebugger::show($query, $place);
        }
        $rows = $this->connection()->select($query, $place);
        if ($rows) {
            $Records = new Records($Find->Tree);
            return $Records->handleRows($rows);
        }
        return [];
    }

    /**
     * @param array $collection
     * @param array|null $defaults
     * @return array|Record|null
     */
    public function findOrCreate(array $collection, array $defaults = null)
    {
        $it = null;
        $this->connection()->transaction(function () use ($collection, $defaults, &$it) {
            $it = $this->findOne(['where' => $collection]);
            if (!$it) {
                $it = $this->create(array_merge($collection, $defaults ?: []));
            }
        });
        return $it;
    }

    /**
     * @return bool
     */
    public function isTimestamps()
    {
        if (isset($this->Definition->timestamps) && is_bool($this->Definition->timestamps)) {
            return $this->Definition->timestamps;
        }
        return true;
    }

    /**
     * @return string
     */
    public function getCreatedAtAttr()
    {
        if (isset($this->Definition->createdAt) && $this->Definition->createdAt) {
            return $this->Definition->createdAt;
        }
        return 'created_at';
    }

    /**
     * @return string
     */
    public function getUpdatedAtAttr()
    {
        if (isset($this->Definition->updatedAt) && $this->Definition->updatedAt) {
            return $this->Definition->updatedAt;
        }
        return 'updated_at';
    }

}
