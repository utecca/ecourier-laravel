<?php

declare(strict_types=1);

use Ecourier\Laravel\Jobs\ProcessEcourierWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\SignatureValidator\DefaultSignatureValidator;
use Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile;
use Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo;

return [
    'api_key' => env('ECOURIER_API_KEY'),

    'webhooks' => [
        'enabled' => env('ECOURIER_WEBHOOKS_ENABLED', true),
        'path' => env('ECOURIER_WEBHOOK_PATH', 'webhooks/ecourier'),
        'domain' => null,
        'name' => 'ecourier',
        'signing_secret' => env('ECOURIER_WEBHOOK_SECRET'),
        'signature_header_name' => env('ECOURIER_WEBHOOK_SIGNATURE_HEADER', 'Signature'),
        'signature_validator' => DefaultSignatureValidator::class,
        'webhook_profile' => ProcessEverythingWebhookProfile::class,
        'webhook_response' => DefaultRespondsTo::class,
        'webhook_model' => env('ECOURIER_WEBHOOK_MODEL', WebhookCall::class),
        'store_headers' => [],
        'process_webhook_job' => ProcessEcourierWebhookJob::class,
    ],
];
