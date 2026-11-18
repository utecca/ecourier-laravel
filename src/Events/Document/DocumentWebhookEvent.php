<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Events\Document;

use Ecourier\Data\Responses\DocumentData;
use Ecourier\Data\Responses\Webhook\DocumentWebhook;
use Ecourier\Enums\WebhookEventType;
use Spatie\WebhookClient\Models\WebhookCall;

/**
 * Base class for the events fired for one type of document webhook.
 */
abstract class DocumentWebhookEvent
{
    /** The document the webhook is about. Same as $webhook->document. */
    public readonly DocumentData $document;

    public function __construct(
        public readonly DocumentWebhook $webhook,
        public readonly WebhookCall $webhookCall,
    ) {
        $this->document = $webhook->document;
    }

    /**
     * The webhook type this event is fired for.
     */
    abstract public static function type(): WebhookEventType;
}
