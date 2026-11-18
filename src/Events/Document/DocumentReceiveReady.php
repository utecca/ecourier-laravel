<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Events\Document;

use Ecourier\Enums\WebhookEventType;

/**
 * Fired for the `Document.Receive.Ready` webhook. A received document is ready to process. Fires each time its status changes to Ready.
 */
class DocumentReceiveReady extends DocumentWebhookEvent
{
    public static function type(): WebhookEventType
    {
        return WebhookEventType::DocumentReceiveReady;
    }
}
