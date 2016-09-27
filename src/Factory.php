<?php

namespace Enjoin;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as Validator;
use Illuminate\Cache\CacheManager;
use Illuminate\Redis\Database;
use Illuminate\Cache\MemcachedConnector;
use Illuminate\Container\Container;
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
     * @return mixed|null
     */
    public static function getCache()
    {
        $Factory = self::getInstance();
        if (!$Factory->Cache) {
            if ($Factory->App) {

                # Use Laravel cache:
                $Factory->Cache = $Factory->App['cache'];

            } else {

                # Create cache manager:
                if ($cache = $Factory->config['cache']) {
                    switch ($cache['default']) {
                        case 'redis':
                            $Factory->Container['redis'] = new Database($Factory->config['database']['redis']);
                            break;
                        case 'memcached':
                            $Factory->Container['memcached.connector'] = new MemcachedConnector;
                            break;
                    }
                    $CacheManager = new CacheManager($Factory->Container);
                    $Factory->Cache = $CacheManager->store();
                }

            }
        }
        return $Factory->Cache;
    }

    /**
     * @return Container|null
     */
    public static function getApp()
    {
        return self::getInstance()->App;
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
