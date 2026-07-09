<?php

declare(strict_types=1);

use Ecourier\EcourierConnector;
use Ecourier\Laravel\Jobs\ProcessEcourierWebhookJob;
use Illuminate\Support\Facades\Route;
use Spatie\WebhookClient\Models\WebhookCall;

it('binds the ecourier connector', function () {
    expect(app(EcourierConnector::class))->toBeInstanceOf(EcourierConnector::class);
});

it('registers the default webhook client config', function () {
    $config = collect(config('webhook-client.configs'))->firstWhere('name', 'ecourier');

    expect($config)
        ->not->toBeNull()
        ->and($config['signing_secret'])->toBeNull()
        ->and($config['signature_header_name'])->toBe('Signature')
        ->and($config['webhook_model'])->toBe(WebhookCall::class)
        ->and($config['process_webhook_job'])->toBe(ProcessEcourierWebhookJob::class);
});

it('registers the webhook route by default', function () {
    expect(Route::has('webhook-client-ecourier'))->toBeTrue();
});
