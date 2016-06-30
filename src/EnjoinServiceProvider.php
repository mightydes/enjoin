<?php

namespace Enjoin;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Config\Repository as ConfigContract;

class EnjoinServiceProvider extends ServiceProvider
{

    private $options = [
        'enjoin' => [
            'lang_dir' => 'vendor/caouecs/laravel4-lang'
        ]
    ];

    /**
     * @param ConfigContract $config
     */
    public function boot(ConfigContract $config)
    {
        $this->options = array_merge($config->get('database'), $this->options);
    }

    /**
     * Register enjoin.
     */
    public function register()
    {
        $this->app->bind('enjoin', function () {
            // TODO: pass $this->app as Container.
            Factory::bootstrap($this->options);
            return new Enjoin;
        });
    }

}
