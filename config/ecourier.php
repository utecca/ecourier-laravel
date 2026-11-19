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

        // Skip a webhook whose event_id was already received and didn't fail, e.g. a retried or resent delivery.
        // Each event_id is remembered in cache_store for duplicate_ttl seconds. Leave cache_store unset to use
        // your default store. Use redis, memcached, database or dynamodb: only those make the check atomic.
        'skip_duplicates' => env('ECOURIER_WEBHOOK_SKIP_DUPLICATES', true),
        'duplicate_ttl' => (int) env('ECOURIER_WEBHOOK_DUPLICATE_TTL', 86400),
        'cache_store' => env('ECOURIER_WEBHOOK_CACHE_STORE'),
    ],
];
