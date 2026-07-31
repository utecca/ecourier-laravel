<?php

declare(strict_types=1);

use Ecourier\Laravel\EcourierServiceProvider;

beforeEach(function () {
    $this->app['config']->set('ecourier.webhook.signing_secret', 'secret');
    $this->app->getProvider(EcourierServiceProvider::class)->packageBooted();
});

it('returns a 400 instead of a 500 when the webhook signature is invalid', function () {
    $response = $this->postJson('webhooks/ecourier', ['foo' => 'bar'], [
        'Signature' => 'invalid-signature',
    ]);

    $response->assertStatus(400);
});

it('returns a 400 instead of a 500 when the webhook signature is missing', function () {
    $response = $this->postJson('webhooks/ecourier', ['foo' => 'bar']);

    $response->assertStatus(400);
});
