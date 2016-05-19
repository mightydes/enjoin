<?php

namespace Enjoin;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Enjoin\Exceptions\Error;

class Bootstrap
{

    /**
     * Bootstrap constructor.
     * @param Factory $Factory
     * @param array $options
     */
    public function __construct(Factory $Factory, array $options)
    {
        $options = $this->handleOptions($options);
        $Factory->config = $options;
        $Factory->Container = new Container;
        $this->handleDatabaseConnections($Factory->Container, $options['connections']);
    }

    /**
     * @param array $options
     * @return array
     * @throws Exceptions\BootstrapException
     */
    private function handleOptions(array $options)
    {
        isset($options['connections'])
        && is_array($options['connections'])
        && count($options['connections'])
            ?: Error::dropBootstrapException("Missed mandatory 'connections' option!");

        isset($options['default']) || isset($options['connections']['default'])
            ?: Error::dropBootstrapException("Unable to define 'default' connection!");
        if (!isset($options['default']) && isset($options['connections']['default'])) {
            $options['default'] = 'default';
        }

        isset($options['connections'][$options['default']])
            ?: Error::dropBootstrapException("Missed mandatory 'connections.{$options['default']}' option!");

        $options['fetch'] = \PDO::FETCH_CLASS;

        isset($options['enjoin']) ?: $options['enjoin'] = [];
        $options['enjoin'] = $this->handleEnjoinOptions($options['enjoin']);

        return $options;
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
            'lang_dir' => 'resources/lang'
        ], $enjoin);
        $enjoin['models_namespace'][0] === '\\'
            ?: $enjoin['models_namespace'] = '\\' . $enjoin['models_namespace'];
        return $enjoin;
    }

    /**
     * @param Container $Container
     * @param array $connections
     */
    private function handleDatabaseConnections(Container $Container, array $connections)
    {
        $Capsule = new Capsule($Container);
        foreach ($connections as $label => $options) {
            $Capsule->addConnection($options, $label);
        }
        $Capsule->setAsGlobal();
    }

}
