<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Events\Document;

use Ecourier\Enums\WebhookEventType;

/**
 * Fired for the `Document.Receive.Created` webhook. A document arrived for one of your participants. Fires once per document.
 */
class DocumentReceiveCreated extends DocumentWebhookEvent
{
    public static function type(): WebhookEventType
    {
        return WebhookEventType::DocumentReceiveCreated;
    }
}
