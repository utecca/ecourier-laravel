<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Jobs;

use Ecourier\Data\Responses\Webhook\DocumentWebhook;
use Ecourier\Data\Responses\Webhook\WebhookEventFactory;
use Ecourier\Enums\WebhookEventType;
use Ecourier\Laravel\Events\Document\DocumentReceiveCreated;
use Ecourier\Laravel\Events\Document\DocumentReceiveDelivered;
use Ecourier\Laravel\Events\Document\DocumentReceiveReady;
use Ecourier\Laravel\Events\Document\DocumentSendCreated;
use Ecourier\Laravel\Events\Document\DocumentSendDelivered;
use Ecourier\Laravel\Events\Document\DocumentSendFailed;
use Ecourier\Laravel\Events\Document\DocumentWebhookEvent;
use Ecourier\Laravel\Events\EcourierWebhookReceived;
use Ecourier\Laravel\Webhooks\SkipDuplicateWebhooks;
use Exception;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Throwable;

class ProcessEcourierWebhookJob extends ProcessWebhookJob
{
    /** @var list<class-string<DocumentWebhookEvent>> */
    private const TYPED_EVENTS = [
        DocumentSendCreated::class,
        DocumentSendDelivered::class,
        DocumentSendFailed::class,
        DocumentReceiveCreated::class,
        DocumentReceiveReady::class,
        DocumentReceiveDelivered::class,
    ];

    public function handle(): void
    {
        $payload = $this->webhookCall->payload ?? [];
        $event = $payload['event'] ?? null;

        $type = is_string($event) ? WebhookEventType::tryFrom($event) : null;

        // Event types added to the API after this SDK version are skipped instead of failing the job.
        if ($type === null) {
            Log::warning('Skipped an eCourier webhook with an unknown event type.', [
                'event' => $event,
                'webhook_call_id' => $this->webhookCall->getKey(),
            ]);

            return;
        }

        $webhook = WebhookEventFactory::fromArray($payload);

        event(new EcourierWebhookReceived(webhook: $webhook, webhookCall: $this->webhookCall));

        $typedEvent = self::typedEventClass($type);

        if ($typedEvent !== null && $webhook instanceof DocumentWebhook) {
            event(new $typedEvent($webhook, $this->webhookCall));
        }
    }

    /**
     * The event fired for one type of webhook, in addition to EcourierWebhookReceived.
     *
     * @return class-string<DocumentWebhookEvent>|null
     */
    public static function typedEventClass(WebhookEventType $type): ?string
    {
        foreach (self::TYPED_EVENTS as $eventClass) {
            if ($eventClass::type() === $type) {
                return $eventClass;
            }
        }

        // A type added to a newer SDK than this package knows: only EcourierWebhookReceived is fired.
        return null;
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
