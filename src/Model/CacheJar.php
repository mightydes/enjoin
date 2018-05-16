<?php

namespace Enjoin\Model;

use Enjoin\Enjoin;
use Enjoin\Factory;
use Enjoin\Extras;

class CacheJar
{

    const TRUSTED = 'trusted';
    const UNTRUSTED = 'untrusted';

    /**
     * @var Model
     */
    protected $Model;

    /**
     * CacheJar constructor.
     * @param Model $Model
     */
    public function __construct(Model $Model)
    {
        $this->Model = $Model;
    }

    /**
     * @param array $basis
     * @return string
     */
    public function keyify(array $basis)
    {
        return md5(json_encode($basis, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param int $flags
     * @return bool
     */
    public function enabled($flags = 0)
    {
        if (!Extras::isCacheEnabled() || ($flags & Enjoin::NO_CACHE) || ($flags & Enjoin::SQL)) {
            return false;
        }
        if ($flags & Enjoin::CACHE) {
            return true;
        }
        return $this->Model->getDefinition()->cache;
    }

    /**
     * @param array $options
     * @option array 'key' -- required cache key...
     * @option \Closure 'get' -- required get data closure...
     * @option \Enjoin\Model[] 'include' -- optional list of included models...
     * @option array 'parseInclude' -- optional find params array...
     * @param int $flags
     * @return mixed
     */
    public function cachify(array $options = [], $flags = 0)
    {
        if ($this->enabled($flags)) {
            $key = $this->keyify($options['key']);
            $affected = [
                $this->Model->getUnique() => true
            ];

            if (isset($options['parseInclude'])) {
                $this->parseInclude($options['parseInclude'], $affected);
            }

            if (isset($options['include'])) {
                foreach ($options['include'] as $model) {
                    $affected[$model->getUnique()] = true;
                }
            }

            $this->flushIfSomeUntrusted(array_keys($affected));

            $cache = $this->get($key);
            if ($cache) {
                if ($cache instanceof EmptyCache) {
                    return $cache->getValue();
                }
                return $cache;
            }

            $data = $options['get']();
            $this->set($key, $data);
            return $data;
        } else {
            return $options['get']();
        }
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $model_key = $this->Model->getUnique();
        $hash_key = Extras::withCachePrefix($model_key);
        $val = Factory::getRedis()->hGet($hash_key, $key);
        if ($val) {
            $val = unserialize($val);
        }
        return $val;
    }

    /**
     * @param string $key
     * @param mixed $data
     */
    public function set($key, $data)
    {
        $model_key = $this->Model->getUnique();
        $hash_key = Extras::withCachePrefix($model_key);
        if (!$data) {
            $data = new EmptyCache($data);
        }
        Factory::getRedis()->hSet($hash_key, $key, serialize($data));
    }

    /**
     * @param string|null $model_key
     */
    public function setUntrusted($model_key = null)
    {
        if (Extras::isCacheEnabled()) {
            $model_key ?: $model_key = $this->Model->getUnique();
            Factory::getRedis()->hSet(
                Factory::getConfig()['enjoin']['trusted_models_cache'],
                $model_key,
                self::UNTRUSTED
            );
        }
    }

    /**
     * @param string|null $model_key
     */
    public function setTrusted($model_key = null)
    {
        if (Extras::isCacheEnabled()) {
            $model_key ?: $model_key = $this->Model->getUnique();
            Factory::getRedis()->hSet(
                Factory::getConfig()['enjoin']['trusted_models_cache'],
                $model_key,
                self::TRUSTED
            );
        }
    }

    /**
     * @return array
     */
    public function getTrustList()
    {
        $out = [];
        if (Extras::isCacheEnabled()) {
            $out = Factory::getRedis()->hGetAll(Factory::getConfig()['enjoin']['trusted_models_cache']);
        }
        return $out;
    }

    /**
     * @param mixed $findParams
     * @param array $affected
     */
    protected function parseInclude($findParams, array &$affected)
    {
        if (!is_array($findParams)) {
            $findParams = [$findParams];
        }
        array_walk_recursive($findParams, function ($v) use (&$affected) {
            if ($v instanceof Model) {
                $affected[$v->getUnique()] = true;
            }
        });
    }

    /**
     * @param array $affected
     */
    protected function flushIfSomeUntrusted(array $affected)
    {
        $list = $this->getTrustList();
        $flush = false;
        foreach ($affected as $model_key) {
            if (!isset($list[$model_key]) || $list[$model_key] !== self::TRUSTED) {
                $flush = true;
                break;
            }
        }
        if ($flush) {
            foreach ($affected as $model_key) {
                Factory::getRedis()->del(Extras::withCachePrefix($model_key));
                $this->setTrusted($model_key);
            }
        }
    }

}
