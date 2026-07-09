<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Events;

use Ecourier\Data\Webhook\DocumentWebhook;
use Spatie\WebhookClient\Models\WebhookCall;

class EcourierWebhookReceived
{
    public function __construct(
        public readonly DocumentWebhook $webhook,
        public readonly WebhookCall $webhookCall,
    ) {}
}
