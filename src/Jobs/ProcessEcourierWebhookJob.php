<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Jobs;

use Ecourier\Data\Webhook\WebhookEvent;
use Ecourier\Laravel\Events\EcourierWebhookReceived;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessEcourierWebhookJob extends ProcessWebhookJob
{
    public function handle(): void
    {
        event(new EcourierWebhookReceived(
            webhook: WebhookEvent::fromArray($this->webhookCall->payload),
            webhookCall: $this->webhookCall,
        ));
    }
}
