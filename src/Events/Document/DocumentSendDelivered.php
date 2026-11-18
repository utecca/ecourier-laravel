<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Events\Document;

use Ecourier\Enums\WebhookEventType;

/**
 * Fired for the `Document.Send.Delivered` webhook. The document was delivered to the recipient's access point.
 */
class DocumentSendDelivered extends DocumentWebhookEvent
{
    public static function type(): WebhookEventType
    {
        return WebhookEventType::DocumentSendDelivered;
    }
}
