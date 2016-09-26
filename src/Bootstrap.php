<?php

namespace Enjoin;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Enjoin\Exceptions\Error;
use RecursiveIteratorIterator, RecursiveArrayIterator;

class Bootstrap
{

    /**
     * Bootstrap constructor.
     * @param Factory $Factory
     * @param array $options
     */
    public function __construct(Factory $Factory, array $options)
    {
        $options['database'] = $this->handleDatabaseOptions($options['database']);

        isset($options['enjoin']) ?: $options['enjoin'] = [];
        $options['enjoin'] = $this->handleEnjoinOptions($options['enjoin']);

        isset($options['cache']) ?: $options['cache'] = null;

        $options = array_merge($options, $this->flattenOptions($options));
        $Factory->config = $options;
        $Factory->Container = new Container;
        $Factory->Container['config'] = $options;
        $this->handleDatabaseConnections($options['database']['connections']);
    }

    /**
     * @param array $database
     * @return array
     * @throws Exceptions\BootstrapException
     */
    private function handleDatabaseOptions(array $database)
    {
        isset($database['connections'])
        && is_array($database['connections'])
        && count($database['connections'])
            ?: Error::dropBootstrapException("Missed mandatory 'connections' option!");

        isset($database['default']) || isset($database['connections']['default'])
            ?: Error::dropBootstrapException("Unable to define 'default' connection!");
        if (!isset($database['default']) && isset($database['connections']['default'])) {
            $database['default'] = 'default';
        }

        isset($database['connections'][$database['default']])
            ?: Error::dropBootstrapException("Missed mandatory 'connections.{$database['default']}' option!");

        $database['fetch'] = \PDO::FETCH_CLASS;

        return $database;
    }

    /**
     * @param array $enjoin
     * @return array
     */
    private function handleEnjoinOptions(array $enjoin)
    {
        $enjoin = array_merge([
            'models_namespace' => '\Models',
            'locale' => 'en',
            'lang_dir' => 'resources/lang',
            'auto_require' => true
        ], $enjoin);
        $enjoin['models_namespace'][0] === '\\'
            ?: $enjoin['models_namespace'] = '\\' . $enjoin['models_namespace'];
        return $enjoin;
    }

    /**
     * @param array $options
     * @return array
     */
    private function flattenOptions(array $options)
    {
        $out = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($options), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $k => $v) {
            if ($iterator->getDepth()) {
                for ($p = [], $i = 0, $z = $iterator->getDepth(); $i <= $z; $i++) {
                    $p [] = $iterator->getSubIterator($i)->key();
                }
                $out[join('.', $p)] = $v;
            }
        }
        return $out;
    }

    /**
     * @param array $connections
     */
    private function handleDatabaseConnections(array $connections)
    {
        $Capsule = new Capsule;
        foreach ($connections as $label => $options) {
            $Capsule->addConnection($options, $label);
        }
        $Capsule->setAsGlobal();
    }

}
