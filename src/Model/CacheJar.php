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
        if ($flags & Enjoin::NO_CACHE) {
            return false;
        }
        if ($flags & Enjoin::WITH_CACHE) {
            return true;
        }
        return $this->Model->Definition->cache;
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
     * @param string $key
     * @return mixed
     */
    public function has($key)
    {
        return Factory::getCache()
            ->tags($this->Model->unique)
            ->has($key);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return Factory::getCache()
            ->tags($this->Model->unique)
            ->get($key);
    }

    /**
     * @param string $key
     * @param mixed $data
     * @return mixed
     */
    public function forever($key, $data)
    {
        return Factory::getCache()
            ->tags($this->Model->unique)
            ->forever($key, $data);
    }

}
