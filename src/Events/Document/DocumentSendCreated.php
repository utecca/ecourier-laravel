<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Events\Document;

use Ecourier\Enums\WebhookEventType;

/**
 * Fired for the `Document.Send.Created` webhook. A document you sent was accepted by the API.
 */
class DocumentSendCreated extends DocumentWebhookEvent
{
    public static function type(): WebhookEventType
    {
        return WebhookEventType::DocumentSendCreated;
    }
}
