<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Webhooks;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Makes sure a webhook event is processed once, so a retried or resent delivery doesn't run your listeners twice.
 *
 * The job claims each event_id with Cache::add() on the store set in ecourier.webhook.cache_store (the app's
 * default store when unset), storing the webhook call that owns it. That is atomic on the Redis, Memcached,
 * database and DynamoDB stores; on the file store, and the array store (which only lasts one request),
 * duplicates can still get through. The claim is released when the job fails, so a resend is processed again.
 *
 * @internal
 */
final class SkipDuplicateWebhooks
{
    public static function enabled(): bool
    {
        return (bool) config('ecourier.webhook.skip_duplicates', true);
    }

    /**
     * Claims an event for a webhook call. Returns false when another webhook call already claimed it.
     * A retry of the job for the same webhook call keeps its claim.
     */
    public static function claim(string $eventId, string $owner): bool
    {
        $cache = self::cache();
        $key = self::key($eventId);

        return $cache->add($key, $owner, config()->integer('ecourier.webhook.duplicate_ttl', 86400))
            || $cache->get($key) === $owner;
    }

    /**
     * Releases an event claimed by this webhook call, so its next delivery is processed.
     */
    public static function release(string $eventId, string $owner): void
    {
        $cache = self::cache();
        $key = self::key($eventId);

        if ($cache->get($key) === $owner) {
            $cache->forget($key);
        }
    }

    private static function cache(): Repository
    {
        $store = config('ecourier.webhook.cache_store');

        return Cache::store(is_string($store) && $store !== '' ? $store : null);
    }

    private static function key(string $eventId): string
    {
        return 'ecourier:webhook:'.$eventId;
    }
}
