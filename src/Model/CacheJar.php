<?php

namespace Enjoin\Model;

use Enjoin\Enjoin;
use Enjoin\Factory;
use Closure;

class CacheJar
{

    /**
     * @var Model
     */
    protected $Model;

    /**
     * @var \Illuminate\Cache\TaggedCache
     */
    private $TaggedCache;

    /**
     * CacheJar constructor.
     * @param Model $Model
     */
    public function __construct(Model $Model)
    {
        $this->Model = $Model;
        if ($Cache = Factory::getCache()) {
            $this->TaggedCache = $Cache->tags([$this->Model->getUnique()]);
        }
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
        if (($flags & Enjoin::NO_CACHE) || ($flags & Enjoin::SQL)) {
            return false;
        }
        if ($flags & Enjoin::WITH_CACHE) {
            return true;
        }
        return $this->Model->getDefinition()->cache;
    }

    public function cachify(array $keyBasis, Closure $getDataFn, $flags = 0)
    {
        if ($this->enabled($flags)) {
            $key = $this->keyify($keyBasis);
            if ($this->has($key)) {
                return $this->get($key);
            }
            $data = $getDataFn();
            $this->forever($key, $data);
            return $data;
        } else {
            return $getDataFn();
        }
    }

    /**
     * @return \Illuminate\Cache\TaggedCache
     */
    public function getCacheInstance()
    {
        return $this->TaggedCache;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->getCacheInstance()->has($key);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->getCacheInstance()->get($key);
    }

    /**
     * @param string $key
     * @param mixed $data
     */
    public function forever($key, $data)
    {
        $this->getCacheInstance()->forever($key, $data);
    }

    /**
     * Flush cache.
     */
    public function flush()
    {
        if ($Cache = Factory::getCache()) {
            $tags = [];
            $this->getFlushTags($tags);
            if ($tags) {
                $Cache->tags($tags)->flush();
            }
        }
    }

    /**
     * @param array $tags
     * @return null
     */
    public function getFlushTags(array &$tags)
    {
        $unique = $this->Model->getUnique();
        if (in_array($unique, $tags)) {
            return null;
        }
        $tags [] = $unique;
        foreach ($this->Model->getDefinition()->getRelations() as $relation) {
            $relation->Model->cache()->getFlushTags($tags);
        }
    }

}
