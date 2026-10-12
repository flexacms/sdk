<?php

namespace FlexaCms\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class FlexaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/flexa.php', 'flexa');

        $this->app->singleton(Flexa::class, fn (Application $app): Flexa => new Flexa($app['config']['flexa']));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/flexa.php' => config_path('flexa.php')], 'flexa-config');
        }
    }
}
