<?php

namespace Dovutuan\Laracom\DomRepository;

use Closure;
use Exception;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     * @throws Exception
     */
    public function register(): void
    {
//        // build file config
//        // create file config
//        $configPath = __DIR__ . '/config/laracom.php';
//        $this->mergeConfigFrom($configPath, 'laracom');
//
//        // publishes file config
//        $this->publishes([__DIR__ . '/config/laracom.php' => config_path('laracom.php')], 'laracom');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}