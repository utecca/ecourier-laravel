<?php

declare(strict_types=1);

use Ecourier\Laravel\Events\EcourierWebhookReceived;
use Ecourier\Laravel\Jobs\ProcessEcourierWebhookJob;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookClient\Models\WebhookCall;

it('parses the webhook payload and dispatches an event', function () {
    Event::fake([EcourierWebhookReceived::class]);

    $webhookCall = new WebhookCall([
        'name' => 'ecourier',
        'url' => 'https://example.test/webhooks/ecourier',
        'headers' => [],
        'payload' => json_decode(file_get_contents(__DIR__.'/../Fixtures/webhook-document.json'), true, flags: JSON_THROW_ON_ERROR),
    ]);

    (new ProcessEcourierWebhookJob($webhookCall))->handle();

    Event::assertDispatched(EcourierWebhookReceived::class, function (EcourierWebhookReceived $event) use ($webhookCall): bool {
        return $event->webhook->eventId === 'evt_01hxyz'
            && $event->webhook->document->id === 'doc_01xyz'
            && $event->webhookCall === $webhookCall;
    });
});
