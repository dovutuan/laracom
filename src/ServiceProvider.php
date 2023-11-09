<?php

namespace Dovutuan\Laracom;

use Dovutuan\Laracom\DomRepository\Command\MakeRepositoryCommand;
use Dovutuan\Laracom\DomRepository\Command\MakeServiceCommand;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // publishes file config
        $this->publishes(
            [
                __DIR__ . '/DomRepository/config/laracom.php' => config_path('laracom.php')
            ],
            'laracom');

        // create file config
        $configPath = __DIR__ . '/DomRepository/config/laracom.php';
        $this->mergeConfigFrom($configPath, 'laracom');

        if ($this->app->runningInConsole()) {
            $this->commands([MakeServiceCommand::class, MakeRepositoryCommand::class]);
        }
    }
}
