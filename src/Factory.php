<?php

namespace Enjoin;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as Validator;
use Illuminate\Redis\RedisManager;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Enjoin\Record\Getters;
use Enjoin\Record\Setters;

class Factory
{

    /**
     * @var Container|null
     */
    public $Container = null;

    /**
     * @var Container|null
     */
    public $App = null;

    public $config = [];

    /**
     * List of cached models.
     * @var array
     */
    public $models = [];

    /**
     * @var Validator|null
     */
    protected $Validator = null;

    /**
     * @var \Illuminate\Redis\RedisManager|null
     */
    protected $Redis = null;

    /**
     * @var Getters|null
     */
    protected $Getters = null;

    /**
     * @var Setters|null
     */
    protected $Setters = null;

    /**
     * @var $this
     */
    private static $instance;

    /**
     * Factory constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return Factory
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * @param array $options
     * @param Container|null $app
     * @return Factory
     */
    public static function bootstrap(array $options, Container $app = null)
    {
        $Factory = self::getInstance();
        new Bootstrap($Factory, $options, $app);
        return $Factory;
    }

    /**
     * @return array
     */
    public static function getConfig()
    {
        return self::getInstance()->config;
    }

    /**
     * @return Validator
     */
    public static function getValidator()
    {
        $Factory = self::getInstance();
        if (!$Factory->Validator) {

            if ($Factory->App) {

                # Use Laravel validator:
                $Factory->Validator = $Factory->App['validator'];

            } else {

                # Create validator:
                $FileLoader = new FileLoader(new Filesystem, $Factory->config['enjoin']['lang_dir']);
                $Translator = new Translator($FileLoader, $Factory->config['enjoin']['locale']);
                $Factory->Validator = new Validator($Translator, $Factory->Container);

            }

        }
        return $Factory->Validator;
    }

    /**
     * @return \Illuminate\Redis\RedisManager
     */
    public static function getRedis()
    {
        $Factory = self::getInstance();
        if (!$Factory->Redis) {
            if ($Factory->App) {
                $Factory->Redis = $Factory->App['redis'];
            } else {
                $Factory->Redis = static::createRedisIfNotExists();
            }

            $trusted_models_cache = $Factory->getConfig()['enjoin']['trusted_models_cache'];
            if (!$Factory->Redis->exists($trusted_models_cache)) {
                $Factory->Redis->hSet($trusted_models_cache, '__TRUSTED_MODELS_CACHE__', date('Y-m-d H:i:s'));
            }
        }
        return $Factory->Redis;
    }

    /**
     * @param null|string $key
     * @return \Illuminate\Database\Connection
     */
    public static function getConnection($key = null)
    {
        $Factory = self::getInstance();
        $key ?: $key = $Factory->getConfig()['database']['default'];
        if ($Factory->App) {
            return $Factory->App['db']->connection($key);
        }
        return Capsule::connection($key);
    }

    /**
     * @return Getters|null
     */
    public static function getGetters()
    {
        $Factory = self::getInstance();
        if (!$Factory->Getters) {
            $Factory->Getters = new Getters;
        }
        return $Factory->Getters;
    }

    /**
     * @return Setters|null
     */
    public static function getSetters()
    {
        $Factory = self::getInstance();
        if (!$Factory->Setters) {
            $Factory->Setters = new Setters;
        }
        return $Factory->Setters;
    }

    /**
     * @return \Illuminate\Redis\RedisManager
     */
    protected static function createRedisIfNotExists()
    {
        $Factory = self::getInstance();
        if (!isset($Factory->Container['redis'])) {
            $Factory->Container['redis'] = new RedisManager('predis', $Factory->getConfig()['database']['redis']);
        }
        return $Factory->Container['redis'];
    }

}
