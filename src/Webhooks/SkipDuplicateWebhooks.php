<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Webhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

/**
 * Skips a webhook whose event was already received, so a retried or resent delivery doesn't run your
 * listeners twice. An event whose earlier call failed is processed again.
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

        /** @var class-string<WebhookCall> $model */
        $model = config('ecourier.webhook.webhook_model', WebhookCall::class);

        return ! $model::query()
            ->where('name', config('ecourier.webhook.name'))
            ->where('payload->event_id', $eventId)
            ->whereNull('exception')
            ->exists();
    }
}
