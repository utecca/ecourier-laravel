<?php

declare(strict_types=1);

use Spatie\WebhookClient\Models\WebhookCall;

return [
    'api_key' => env('ECOURIER_API_KEY'),

    'webhook' => [
        'enabled' => env('ECOURIER_WEBHOOK_ENABLED', true),
        'path' => env('ECOURIER_WEBHOOK_PATH', 'webhooks/ecourier'),
        'domain' => null,
        'name' => 'ecourier',
        'signing_secret' => env('ECOURIER_WEBHOOK_SECRET'),
        'webhook_model' => env('ECOURIER_WEBHOOK_MODEL', WebhookCall::class),
    ],
];
