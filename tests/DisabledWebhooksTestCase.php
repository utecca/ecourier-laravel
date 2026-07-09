<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Tests;

class DisabledWebhooksTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('ecourier.webhooks.enabled', false);
    }
}
