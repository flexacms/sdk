<?php

namespace FlexaCms\Laravel\Tests;

use FlexaCms\Laravel\FlexaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [FlexaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('flexa.url', 'https://cms.test');
        $app['config']->set('flexa.api_key', 'flx_test');
        $app['config']->set('cache.default', 'array');
    }
}
