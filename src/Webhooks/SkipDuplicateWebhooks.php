<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

/**
 * Skips a webhook whose event was already received, so a retried or resent delivery doesn't run your
 * listeners twice. Each event_id is claimed with Cache::add() on the app's default cache store, which is
 * atomic on the Redis, Memcached, database and DynamoDB stores. The claim is released when processing fails, so a resend is processed again.
 */
class SkipDuplicateWebhooks implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        if (! config('ecourier.webhook.skip_duplicates', true)) {
            return true;
        }

        $eventId = $request->input('event_id');

        if (! is_string($eventId) || $eventId === '') {
            return true;
        }

        return Cache::add(self::key($eventId), true, config()->integer('ecourier.webhook.duplicate_ttl', 86400));
    }

    /**
     * Releases the claim on an event, so its next delivery is processed.
     */
    public static function release(string $eventId): void
    {
        Cache::forget(self::key($eventId));
    }

    private static function key(string $eventId): string
    {
        return 'ecourier:webhook:'.$eventId;
    }
}
