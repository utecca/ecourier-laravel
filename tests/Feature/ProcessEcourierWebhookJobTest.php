<?php

declare(strict_types=1);

use Ecourier\Laravel\Events\EcourierWebhookReceived;
use Ecourier\Laravel\Jobs\ProcessEcourierWebhookJob;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
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

it('skips a webhook with an unknown event type', function () {
    Event::fake([EcourierWebhookReceived::class]);
    Log::spy();

    $webhookCall = new WebhookCall([
        'name' => 'ecourier',
        'url' => 'https://example.test/webhooks/ecourier',
        'headers' => [],
        'payload' => ['event_id' => 'evt_01new', 'event' => 'Company.Created'],
    ]);

    (new ProcessEcourierWebhookJob($webhookCall))->handle();

    Event::assertNotDispatched(EcourierWebhookReceived::class);
    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $context['event'] === 'Company.Created');
});

it('records a failure on the webhook call', function () {
    (include dirname(__DIR__, 2).'/vendor/spatie/laravel-webhook-client/database/migrations/create_webhook_calls_table.php.stub')->up();

    $webhookCall = WebhookCall::create([
        'name' => 'ecourier',
        'url' => 'https://example.test/webhooks/ecourier',
        'headers' => [],
        'payload' => ['event_id' => 'evt_01hxyz'],
    ]);

    (new ProcessEcourierWebhookJob($webhookCall))->failed(new Error('Listener failed'));

    expect($webhookCall->fresh()->exception['message'])->toBe('Listener failed');
});
