<?php

namespace Enjoin\Model;

use Doctrine\Common\Inflector\Inflector;
use Enjoin\Enjoin;
use Enjoin\Extras;
use Enjoin\Exceptions\Error;
use Enjoin\Factory;
use Enjoin\Record\Record;
use Enjoin\Record\Records;
use Enjoin\Builder\Find;
use Enjoin\Builder\Count;
use Enjoin\Builder\Destroy;
use Enjoin\Builder\Update;
use PdoDebugger;

/**
 * Class Model
 * @package Enjoin\Model
 */
class Model
{

    /**
     * Model definition instance.
     * @var \Enjoin\Model\Definition
     */
    private $Definition;

    /**
     * @var null|\Enjoin\Dialectify\Dialectify
     */
    private $Dialectify = null;

    /**
     * @var \Enjoin\Model\CacheJar
     */
    private $CacheJar;

    private $unique;

    /**
     * Model constructor.
     * @param \Enjoin\Model\Definition $Definition
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
        $this->CacheJar = new CacheJar($this);
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    public function connection()
    {
        return Factory::getConnection($this->getDefinition()->connection);
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function queryBuilder()
    {
        return $this->connection()->table($this->getTableName());
    }

    /**
     * @param array|object|null $collection
     * @return \Enjoin\Record\Record
     */
    public function build($collection = null)
    {
        $collection ?: $collection = [];
        $recordClass = $this->getDefinition()->expanseRecord ?: Record::class;
        $Record = new $recordClass($this);
        foreach ($collection as $k => $v) {
            $Record->$k = $v;
        }
        return $Record;
    }

    /**
     * @param array|object|null $collection
     * @param array|null $params
     * @return \Enjoin\Record\Record
     */
    public function create($collection = null, array $params = null)
    {
        if (isset($params['fields'])) {
            $collection = Extras::pick($collection, $params['fields']);
        }
        $this->cache()->flush();
        return $this->build($collection)->save();
    }

    /**
     * @todo: Add second argument: array|null $params (see http://docs.sequelizejs.com/en/v3/api/model/).
     * @param array $collections
     * @return bool
     */
    public function bulkCreate(array $collections)
    {
        $bulk = [];
        $Setters = Factory::getSetters();
        $defAttributes = $this->getDefinition()->getAttributes();
        foreach ($collections as $record) {
            $volume = [];
            $skip = [];
            # Perform timestamps:
            if ($this->isTimestamps()) {
                # Created at:
                $createdAtField = $this->getCreatedAtField();
                $volume[$createdAtField] = $Setters->getCreatedAt($this, isset($record[$createdAtField]) ? $record[$createdAtField] : null);
                $skip [] = $createdAtField;
                # Updated at:
                $updatedAtField = $this->getUpdatedAtField();
                $volume[$updatedAtField] = $Setters->getUpdatedAt($this);
                $skip [] = $updatedAtField;
            }
            # Perform setters:
            $validate = [];
            foreach (array_diff(array_keys($record), $skip) as $attr) {
                if (array_key_exists($attr, $defAttributes)) {
                    $volume[$attr] = $Setters->perform($this, $record, $defAttributes[$attr], $attr);
                    if (isset($defAttributes[$attr]['validate'])) {
                        $validate [] = [$attr, $volume[$attr], $defAttributes[$attr]['validate']];
                    }
                }
            }
            !$validate ?: $Setters->validate($validate);
            $bulk [] = $volume;
        }
        $this->cache()->flush();
        return $this->queryBuilder()->insert($bulk);
    }

    /**
     * @param array $collection
     * @param array|null $params
     * @param int $flags
     * @return int|mixed
     */
    public function update(array $collection, array $params = null, $flags = 0)
    {
        $where = isset($params['where']) ? $params['where'] : null;
        $Update = new Update($this, $collection, $where);
        list($query, $place) = $Update->getPrepared();
        if ($flags & Enjoin::SQL) {
            return PdoDebugger::show($query, $place);
        }
        $affected = $this->connection()->update($query, $place);
        $this->cache()->flush();
        return $affected;
    }

    /**
     * @param array|null $params
     * @param int $flags
     * @return int|mixed
     */
    public function destroy(array $params = null, $flags = 0)
    {
        if (!isset($params['where'])) {
            $query = "DELETE FROM `{$this->getTableName()}`";
            return $flags & Enjoin::SQL ? $query : $this->connection()->update($query);
        }

        $Destroy = new Destroy($this, $params['where']);
        list($query, $place) = $Destroy->getPrepared();
        if ($flags & Enjoin::SQL) {
            return PdoDebugger::show($query, $place);
        }
        $affected = $this->connection()->update($query, $place);
        $this->cache()->flush();
        return $affected;
    }

    /**
     * @todo: call without params feature.
     * @param array $params
     * @param int $flags
     * @return \Enjoin\Record\Record|null|array
     */
    public function findOne(array $params, $flags = 0)
    {
        return $this->cache()->cachify([__FUNCTION__, $params], function () use ($params, $flags) {
            unset($params['offset']);
            $params['limit'] = 1;
            $Find = new Find($this, $params);
            list($query, $place) = $Find->getPrepared();
            if ($flags & Enjoin::SQL) {
//                !Enjoin::debug() ?: sd($query, $place);
                return PdoDebugger::show($query, $place);
            }
            $rows = $this->connection()->select($query, $place);
            if ($rows) {
                $Records = new Records($Find->Tree);
                $out = $Records->handleRows($rows)[0];
                return $out;
            }
            return null;
        }, $flags);
    }

    /**
     * @param int $id
     * @param int $flags
     * @return \Enjoin\Record\Record|null
     */
    public function findById($id, $flags = 0)
    {
        return $this->findOne(['where' => ['id' => $id]], $flags);
    }

    /**
     * @deprecated Use `findById()` and `findOne()` instead.
     * @param array|integer $params
     * @param int $flags
     * @return \Enjoin\Record\Record|null
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
     * @return \Enjoin\Record\Record[]
     */
    public function findAll(array $params = null, $flags = 0)
    {
        $params ?: $params = [];
        return $this->cache()->cachify([__FUNCTION__, $params], function () use ($params, $flags) {
            $Find = new Find($this, $params);
            list($query, $place) = $Find->getPrepared();
            if ($flags & Enjoin::SQL) {
                return PdoDebugger::show($query, $place);
            }
            if ($rows = $this->connection()->select($query, $place)) {
                $Records = new Records($Find->Tree);
                return $Records->handleRows($rows);
            }
            return [];
        }, $flags);
    }

    /**
     * @param array $params
     * @return \Enjoin\Record\Record
     */
    public function findOrCreate(array $params)
    {
        $it = null;
        $this->connection()->transaction(function () use ($params, &$it) {
            $it = $this->findOne(['where' => $params['where']]);
            if (!$it) {
                $collection = isset($params['defaults'])
                    ? array_merge($params['where'], $params['defaults'])
                    : $params['where'];
                $it = $this->create($collection);
            }
        });
        return $it;
    }

    /**
     * @param array $params
     * @return \Enjoin\Record\Record
     */
    public function findCreateFind(array $params)
    {
        $it = $this->findOne(['where' => $params['where']]);
        if (!$it) {
            $collection = isset($params['defaults'])
                ? array_merge($params['where'], $params['defaults'])
                : $params['where'];
            $this->create($collection);
        }
        return $this->findOne(['where' => $params['where']]);
    }

    /**
     * @param array|null $params
     * @param int $flags
     * @return int
     */
    public function count(array $params = null, $flags = 0)
    {
        $params ?: $params = [];
        return $this->cache()->cachify([__FUNCTION__, $params], function () use ($params, $flags) {
            $Count = new Count($this, $params);
            list($query, $place) = $Count->getPrepared();
            if ($flags & Enjoin::SQL) {
                return PdoDebugger::show($query, $place);
            }
            return (int)$this->connection()->select($query, $place)[0]->count;
        }, $flags);
    }

    /**
     * @todo: Create `findAndCount` alias.
     * @param array|null $params
     * @param int $flags
     * @return array
     */
    public function findAndCountAll(array $params = null, $flags = 0)
    {
        $params ?: $params = [];
        $count = $this->cache()->cachify([__FUNCTION__, $params], function () use ($params, $flags) {
            $Count = new Count($this, $params);
            list($query, $place) = $Count->getPrepared();
            if ($flags & Enjoin::SQL) {
                return PdoDebugger::show($query, $place);
            }
            return (int)$this->connection()->select($query, $place)[0]->count;
        }, $flags);
        if ($flags & Enjoin::SQL) {
            return [
                'count' => $count,
                'rows' => $this->findAll($params, $flags)
            ];
        }
        $rows = [];
        if ($count) {
            $rows = $this->findAll($params, $flags);
        }
        return [
            'count' => $count,
            'rows' => $rows
        ];
    }

    /**
     * @return bool
     */
    public function isTimestamps()
    {
        if (isset($this->getDefinition()->timestamps) && is_bool($this->getDefinition()->timestamps)) {
            return $this->getDefinition()->timestamps;
        }
        return true;
    }

    /**
     * @deprecated use `getCreatedAtField()` instead.
     * @return string
     */
    public function getCreatedAtAttr()
    {
        return $this->getCreatedAtField();
    }

    /**
     * @return string
     */
    public function getCreatedAtField()
    {
        if (isset($this->getDefinition()->createdAt) && $this->getDefinition()->createdAt) {
            return $this->getDefinition()->createdAt;
        }
        return 'created_at';
    }

    /**
     * @deprecated use `getUpdatedAtField()` instead.
     * @return string
     */
    public function getUpdatedAtAttr()
    {
        return $this->getUpdatedAtField();
    }

    /**
     * @return string
     */
    public function getUpdatedAtField()
    {
        if (isset($this->getDefinition()->updatedAt) && $this->getDefinition()->updatedAt) {
            return $this->getDefinition()->updatedAt;
        }
        return 'updated_at';
    }

    /**
     * @return string
     */
    public function getUnique()
    {
        return $this->unique;
    }

    /**
     * @return \Enjoin\Model\Definition
     */
    public function getDefinition()
    {
        return $this->Definition;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->Definition->table;
    }

    /**
     * @return \Enjoin\Dialectify\Dialectify|null
     */
    public function dialectify()
    {
        if (!$this->Dialectify) {
            $map = [
                'mysql' => 'MySql',
                'pgsql' => 'PostgreSql'
            ];
            $driver = $this->connection()->getDriverName();
            isset($map[$driver]) ?: Error::dropModelException("Unknown dialectify driver: '$driver'!");
            $dialect = '\\Enjoin\\Dialectify\\' . $map[$driver];
            $this->Dialectify = new $dialect($this);
        }
        return $this->Dialectify;
    }

    /**
     * @return \Enjoin\Model\CacheJar
     */
    public function cache()
    {
        return $this->CacheJar;
    }

}
