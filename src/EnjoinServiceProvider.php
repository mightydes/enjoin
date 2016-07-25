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
            'enjoin' => [
                'lang_dir' => 'vendor/caouecs/laravel4-lang'
            ],
            'cache' => $config->get('cache')
        ];
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
