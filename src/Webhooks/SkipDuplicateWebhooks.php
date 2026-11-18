<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Webhooks;

use Illuminate\Http\Request;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Redis;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

/**
 * Skips a webhook whose event was already received, so a retried or resent delivery doesn't run your
 * listeners twice. Each event_id is claimed with an atomic Redis SET NX, which expires after the
 * configured time. The claim is released when processing fails, so a resend is processed again.
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

        $ttl = (int) config('ecourier.webhook.duplicate_ttl', 86400);

        return (bool) self::redis()->set(self::key($eventId), '1', 'EX', $ttl, 'NX');
    }

    /**
     * Releases the claim on an event, so its next delivery is processed.
     */
    public static function release(string $eventId): void
    {
        self::redis()->del(self::key($eventId));
    }

    private static function key(string $eventId): string
    {
        return 'ecourier:webhook:'.$eventId;
    }

    private static function redis(): Connection
    {
        return Redis::connection(config('ecourier.webhook.redis_connection'));
    }
}
