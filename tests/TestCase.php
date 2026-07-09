<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Tests;

use Ecourier\Laravel\EcourierServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\WebhookClient\WebhookClientServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            WebhookClientServiceProvider::class,
            EcourierServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ecourier.api_key', 'pk_test_123');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }
}
