<?php

namespace Enjoin;

use DB, Config, Exception, Cache;
use Doctrine\Common\Inflector\Inflector;

// TODO: `bulkCreate` second parameter (an array) to let it know which fields you want to build explicitly.

class Model
{

    const CACHE_PREFIX = 'enjoin';
    const CACHE_EXPIRES = 24; // hours

    /**
     * Model description object.
     * @var BaseModel
     */
    public $Context;

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public function connect()
    {
        $key = $this->Context->connection;
        if ($key === 'default') {
            $key = Config::get('database.default');
        }
        return DB::connection($key)->table($this->Context->table);
    }

    /**
     * @param BaseModel $Context
     */
    public function __construct($Context)
    {
        if (!property_exists($Context, 'table') || !$Context->table) {
            /*
             * Define table name from model name
             * if not performed manually.
             */
            $arr = explode('\\', get_class($Context));
            $Context->table = Inflector::tableize(end($arr));
        }
        $this->Context = $Context;
    }

    /**
     * Returns string representation of a given model object.
     * @return string
     */
    public function getKey()
    {
        return get_class($this->Context);
    }

    /**
     * @return bool
     */
    public function isTimestamps()
    {
        $timestamps = true;
        if (property_exists($this->Context, 'timestamps')) {
            $timestamps = is_bool($this->Context->timestamps)
                ? $this->Context->timestamps
                : true;
        }
        return $timestamps;
    }

    /**
     * @return string
     */
    public function getCreatedAtAttr()
    {
        if (property_exists($this->Context, 'createdAt') && $this->Context->createdAt) {
            return $this->Context->createdAt;
        }
        return Extras::$CREATED_AT_ATTR;
    }

    /**
     * @return string
     */
    public function getUpdatedAtAttr()
    {
        if (property_exists($this->Context, 'updatedAt') && $this->Context->updatedAt) {
            return $this->Context->updatedAt;
        }
        return Extras::$UPDATED_AT_ATTR;
    }

    /**
     * @param array|integer $params
     * @return null|Record
     */
    public function find($params)
    {
        # Check cache
        $cache_key = $this->getCacheKey(__FUNCTION__, $params);
        if ($cache = $this->getCache($cache_key)) {
            if ($cache instanceof EmptyCache) {
                return null;
            }
            return $cache;
        }

        # Handle find by id (ie `->find(1)`)
        if (!is_array($params)) {
            $params = ['where' => ['id' => $params]];
        }

        $Finders = new Finders($this->connect(), $this);
        $Finders->handle($params);
        if (array_key_exists('include', $params)) {
            $rows = $Finders->DB->get();
        } else {
            $rows = $Finders->DB->take(1)->get();
        }
        if ($rows) {
            $Records = new Records($Finders->Handler);
            $result = $Records->handleRows($rows)[0];
            $this->putCache($cache_key, $result);
            return $result;
        }
        $this->putCache($cache_key, new EmptyCache);
        return null;
    }

    /**
     * @param array $params
     * @return array|Record[]
     */
    public function findAll(array $params = [])
    {
        # Check cache
        $cache_key = $this->getCacheKey(__FUNCTION__, $params);
        if ($cache = $this->getCache($cache_key)) {
            if ($cache instanceof EmptyCache) {
                return [];
            }
            return $cache;
        }

        $Finders = new Finders($this->connect(), $this);
        $Finders->handle($params);
        $rows = $Finders->DB->get();
        if ($rows) {
            $Records = new Records($Finders->Handler);
            $result = $Records->handleRows($rows);
            $this->putCache($cache_key, $result);
            return $result;
        }
        $this->putCache($cache_key, new EmptyCache);
        return [];
    }

    /**
     * @param array $params
     * @return array
     */
    public function findAndCountAll(array $params = [])
    {
        # Check cache
        $cache_key = $this->getCacheKey(__FUNCTION__, $params);
        if ($cache = $this->getCache($cache_key)) {
            return $cache;
        }

        $out = [
            'count' => 0,
            'rows' => []
        ];

        if (array_key_exists('include', $params)) {
            # Perform records first
            $Finders = new Finders($this->connect(), $this);
            $Finders->handle($params);
            $rows = $Finders->DB->get();
            if ($rows) {
                $Records = new Records($Finders->Handler);
                $out['rows'] = $Records->handleRows($rows);
                $out['count'] = count($out['rows']);
            }
        } else {
            # Count first
            $Finders = new Finders($this->connect(), $this);
            $Finders->handle($params, ['isCount' => true]);
            if ($count = $Finders->DB->count()) {
                $out['count'] = $count;
                $Finders = new Finders($this->connect(), $this);
                $Finders->handle($params);
                $rows = $Finders->DB->get();
                if ($rows) {
                    $Records = new Records($Finders->Handler);
                    $out['rows'] = $Records->handleRows($rows);
                }
            }
        }

        $this->putCache($cache_key, $out);
        return $out;
    }

    /**
     * @param array $params
     * @return integer
     */
    public function count(array $params = [])
    {
        # Check cache
        $cache_key = $this->getCacheKey(__FUNCTION__, $params);
        $cache = $this->getCache($cache_key);
        if (is_numeric($cache)) {
            return $cache;
        }

        $count = 0;
        $Finders = new Finders($this->connect(), $this);
        if (array_key_exists('include', $params)) {
            # Perform records, then count
            $Finders->handle($params);
            $rows = $Finders->DB->get();
            if ($rows) {
                $Records = new Records($Finders->Handler);
                $count = count($Records->handleRows($rows));
            }
        } else {
            # Perform count as is
            $Finders = new Finders($this->connect(), $this);
            $Finders->handle($params, ['isCount' => true]);
            $count = $Finders->DB->count();
        }

        $this->putCache($cache_key, $count);
        return $count;
    }

    /**
     * @return Record|null|object
     * @throws \Exception
     */
    public function findOrCreate()
    {
        $args = func_get_args();
        if (!$args) {
            throw new Exception('Expected `key => value` array(s) as argument(s).');
        }
        $args = call_user_func_array('array_merge', $args);
        $it = $this->find(['where' => $args]);
        if (!$it) {
            $it = $this->create($args);
        }
        return $it;
    }

    /**
     * @param array $collection
     * @param array $attributes
     * @return Record
     */
    public function build(array $collection = [], array $attributes = [])
    {
        $Record = new Record($this, Extras::$NON_PERSISTENT_RECORD);
        foreach ($collection as $k => $v) {
            if ($attributes) {
                if (in_array($k, $attributes)) {
                    $Record->$k = $v;
                }
            } else {
                $Record->$k = $v;
            }
        }
        return $Record;
    }

    /**
     * @param array $collection
     * @param array $attributes
     * @return Record
     */
    public function create(array $collection, array $attributes = [])
    {
        $this->flushCache();
        return $this->build($collection, $attributes)->save();
    }

    /**
     * @param array $collections
     * @return bool
     */
    public function bulkCreate(array $collections)
    {
        # Handle timestamps
        if ($this->isTimestamps()) {
            $created_at = $this->getCreatedAtAttr();
            $updated_at = $this->getUpdatedAtAttr();
            foreach ($collections as &$v) {
                $v[$created_at] = array_key_exists($created_at, $v)
                    ? Setters::getCreatedAt($v[$created_at])
                    : Setters::getCreatedAt(null, true);
                $v[$updated_at] = Setters::getUpdatedAt();
            }
        }

        $this->flushCache();
        return $this->connect()->insert($collections);
    }

    /**
     * @param array $where
     * @param array $updateCollection
     * @return int
     */
    public function update(array $where, array $updateCollection)
    {
        $this->flushCache();

        $Finders = new Finders($this->connect(), $this);
        $Finders->handle(['where' => $where]);
        return $Finders->DB->update($updateCollection);
    }

    /**
     * @param null|array|object $where
     * @param array $options
     * @return int affected rows
     */
    public function destroy($where = null, array $options = [])
    {
        // TODO: handle `$options['truncate']`

        $this->flushCache();

        if (!$where && !$options) {
            # Delete all records
            return $this->connect()->delete();
        }

        if ($where) {
            $Finders = new Finders($this->connect(), $this);
            $Finders->handle(['where' => $where]);
            return $Finders->DB->delete();
        }

        return 0;
    }

    /**
     * @param string $func_name
     * @param mixed $params
     * @return string
     */
    private function getCacheKey($func_name, $params)
    {
        return self::CACHE_PREFIX . '.'
        . $func_name . '.'
        . md5(json_encode([$params, $this->getKey()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param string $key
     * @return mixed
     */
    private function getCache($key)
    {
        if (!$this->Context->cache) {
            return null;
        }
        return Cache::tags($this->getKey())->get($key);
    }

    /**
     * @param string $key
     * @param mixed $data
     * @return null
     */
    private function putCache($key, $data)
    {
        // TODO: add `cacheExpires` model option.

        if (!$this->Context->cache) {
            return null;
        }
        Cache::tags($this->getKey())->put($key, $data, self::CACHE_EXPIRES * 60);
    }

    /**
     * Flush cache.
     */
    public function flushCache()
    {
        $tags = [];
        $this->getFlushTags($tags);
        if ($tags) {
            Cache::tags($tags)->flush();
        }
    }

    /**
     * @param array $tags
     * @return null
     */
    public function getFlushTags(array &$tags)
    {
        $key = $this->getKey();
        if (in_array($key, $tags)) {
            return null;
        }
        $tags [] = $key;
        foreach ($this->Context->getRelations() as $v) {
            $v['model']->getFlushTags($tags);
        }
    }

} // end of class
