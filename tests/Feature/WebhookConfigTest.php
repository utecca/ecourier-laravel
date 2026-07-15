<?php

declare(strict_types=1);

use Ecourier\Laravel\EcourierServiceProvider;
use Spatie\WebhookClient\Models\WebhookCall;

class CustomWebhookCall extends WebhookCall {}

it('respects a custom webhook model', function () {
    $this->app['config']->set('ecourier.webhook.webhook_model', CustomWebhookCall::class);

    $this->app->getProvider(EcourierServiceProvider::class)->packageBooted();

    $config = collect(config('webhook-client.configs'))->firstWhere('name', 'ecourier');

    expect($config['webhook_model'])->toBe(CustomWebhookCall::class);
});
