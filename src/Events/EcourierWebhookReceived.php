<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Events;

use Ecourier\Data\Responses\Webhook\WebhookEvent;
use Spatie\WebhookClient\Models\WebhookCall;

class EcourierWebhookReceived
{
    public function __construct(
        public readonly WebhookEvent $webhook,
        public readonly WebhookCall $webhookCall,
    ) {}
}
