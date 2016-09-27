<?php

namespace Enjoin;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class EnjoinServiceProvider extends ServiceProvider
{

    private $options = [];

    /**
     * @param ConfigContract $config
     */
    public function boot(ConfigContract $config)
    {
        $this->options = [
            'database' => $config->get('database'),
            'enjoin' => $config->get('enjoin'),
            'cache' => $config->get('cache')
        ];
    }

    /**
     * Register enjoin.
     */
    public function register()
    {
        $this->app->bind('enjoin', function () {
            Factory::bootstrap($this->options, $this->app);
            return new Enjoin;
        });
    }

}
