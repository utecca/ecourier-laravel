<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Jobs;

use Ecourier\Laravel\Events\EcourierWebhookReceived;
use Ecourier\Sdk\Data\Webhook\WebhookEvent;
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
