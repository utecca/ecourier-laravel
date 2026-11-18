<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Events\Document;

use Ecourier\Enums\WebhookEventType;

/**
 * Fired for the `Document.Send.Failed` webhook. The document failed validation or delivery.
 */
class DocumentSendFailed extends DocumentWebhookEvent
{
    public static function type(): WebhookEventType
    {
        return WebhookEventType::DocumentSendFailed;
    }
}
