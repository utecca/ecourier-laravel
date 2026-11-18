<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Events\Document;

use Ecourier\Enums\WebhookEventType;

/**
 * Fired for the `Document.Receive.Delivered` webhook. The received document was marked as delivered.
 */
class DocumentReceiveDelivered extends DocumentWebhookEvent
{
    public static function type(): WebhookEventType
    {
        return WebhookEventType::DocumentReceiveDelivered;
    }
}
