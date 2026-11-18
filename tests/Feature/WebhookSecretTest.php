<?php

declare(strict_types=1);

use Ecourier\Laravel\Exceptions\MissingConfigurationException;

it('throws a clear error when the webhook signing secret is not set', function () {
    $this->withoutExceptionHandling();

    $this->postJson('webhooks/ecourier', ['foo' => 'bar'], ['Signature' => 'anything']);
})->throws(MissingConfigurationException::class, 'ECOURIER_WEBHOOK_SECRET');
