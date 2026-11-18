<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Jobs;

use Ecourier\Data\Responses\Webhook\WebhookEventFactory;
use Ecourier\Enums\WebhookEventType;
use Ecourier\Laravel\Events\EcourierWebhookReceived;
use Ecourier\Laravel\Webhooks\SkipDuplicateWebhooks;
use Exception;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Throwable;

class ProcessEcourierWebhookJob extends ProcessWebhookJob
{
    public function handle(): void
    {
        $payload = $this->webhookCall->payload ?? [];
        $event = $payload['event'] ?? null;

        // Event types added to the API after this SDK version are skipped instead of failing the job.
        if (! is_string($event) || WebhookEventType::tryFrom($event) === null) {
            Log::warning('Skipped an eCourier webhook with an unknown event type.', [
                'event' => $event,
                'webhook_call_id' => $this->webhookCall->getKey(),
            ]);

            return;
        }

        event(new EcourierWebhookReceived(
            webhook: WebhookEventFactory::fromArray($payload),
            webhookCall: $this->webhookCall,
        ));
    }

    /**
     * Records the failure on the stored call, and releases the event so a resent delivery is processed again.
     */
    public function failed(Throwable $exception): void
    {
        $this->webhookCall->saveException(
            $exception instanceof Exception
                ? $exception
                : new Exception($exception->getMessage(), (int) $exception->getCode(), $exception),
        );

        $eventId = $this->webhookCall->payload['event_id'] ?? null;

        if (config('ecourier.webhook.skip_duplicates', true) && is_string($eventId) && $eventId !== '') {
            SkipDuplicateWebhooks::release($eventId);
        }
    }
}
