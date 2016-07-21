<?php

namespace Enjoin;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as Validator;
use Illuminate\Cache\CacheManager;
use Illuminate\Redis\Database;
use Enjoin\Record\Getters;
use Enjoin\Record\Setters;

class Factory
{

    /**
     * @var \Illuminate\Container\Container|null
     */
    public $Container = null;

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

    protected $Cache = null;

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
     * @return $this
     */
    public static function bootstrap(array $options)
    {
        $Factory = self::getInstance();
        new Bootstrap($Factory, $options);
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
            $FileLoader = new FileLoader(new Filesystem, $Factory->config['enjoin']['lang_dir']);
            $Translator = new Translator($FileLoader, $Factory->config['enjoin']['locale']);
            $Factory->Validator = new Validator($Translator, $Factory->Container);
        }
        return $Factory->Validator;
    }

    /**
     * @return mixed|null
     */
    public static function getCache()
    {
        $Factory = self::getInstance();
        if (!$Factory->Cache) {
            if ($cache = $Factory->config['cache']) {
                if ($cache['default'] === 'redis') {
                    $Factory->Container['redis'] = new Database($Factory->config['database']['redis']);
                }
                $CacheManager = new CacheManager($Factory->Container);
                $Factory->Cache = $CacheManager->store();

            }
        }
        return $Factory->Cache;
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

}
