<?php

namespace Enjoin;

use Cache;

class CacheControl
{

    const CACHE_EXPIRES = 24; // hours

    public $tags = [];

    /**
     * @var Model
     */
    private $Model;

    public function __construct(Model $Model)
    {
        $this->Model = $Model;
        $this->tags = ['enjoin', $this->Model->getKey()];
    }

    /**
     * @param string $func_name
     * @param mixed $params
     * @return string
     */
    public function getKey($func_name, $params)
    {
        return $func_name . '.' . md5(json_encode(
            [$params, $this->Model->getKey()],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (!$this->Model->Context->cache) {
            return null;
        }
        return Cache::tags($this->tags)->get($key);
    }

    /**
     * @param string $key
     * @param mixed $data
     * @return null
     */
    public function put($key, $data)
    {
        // TODO: add `cacheExpires` model option.

        if (!$this->Model->Context->cache) {
            return null;
        }
        Cache::tags($this->tags)->put($key, $data, self::CACHE_EXPIRES * 60);
    }

    /**
     * Flush cache.
     */
    public function flush()
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
        $key = $this->Model->getKey();
        if (in_array($key, $tags)) {
            return null;
        }
        $tags [] = $key;
        foreach ($this->Model->Context->getRelations() as $v) {
            $v['model']->CC->getFlushTags($tags);
        }
    }

} // end of class
