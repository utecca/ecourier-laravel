<?php

declare(strict_types=1);

use Ecourier\Enums\WebhookEventType;
use Ecourier\Laravel\Events\Document\DocumentReceiveCreated;
use Ecourier\Laravel\Events\Document\DocumentReceiveDelivered;
use Ecourier\Laravel\Events\Document\DocumentReceiveReady;
use Ecourier\Laravel\Events\Document\DocumentSendCreated;
use Ecourier\Laravel\Events\Document\DocumentSendDelivered;
use Ecourier\Laravel\Events\Document\DocumentSendFailed;
use Ecourier\Laravel\Events\Document\DocumentWebhookEvent;
use Ecourier\Laravel\Events\EcourierWebhookReceived;
use Ecourier\Laravel\Jobs\ProcessEcourierWebhookJob;
use Illuminate\Support\Facades\Cache;
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

it('dispatches a typed event for each webhook type', function (string $type, string $eventClass) {
    Event::fake();

    $payload = json_decode(file_get_contents(__DIR__.'/../Fixtures/webhook-document.json'), true, flags: JSON_THROW_ON_ERROR);
    $payload['event'] = $type;

    $webhookCall = new WebhookCall([
        'name' => 'ecourier',
        'url' => 'https://example.test/webhooks/ecourier',
        'headers' => [],
        'payload' => $payload,
    ]);

    (new ProcessEcourierWebhookJob($webhookCall))->handle();

    Event::assertDispatched(EcourierWebhookReceived::class);
    Event::assertDispatched($eventClass, function (DocumentWebhookEvent $event) use ($webhookCall): bool {
        return $event->document->id === 'doc_01xyz'
            && $event->document === $event->webhook->document
            && $event->webhookCall === $webhookCall;
    });

    foreach (array_diff(typedEventClasses(), [$eventClass]) as $otherClass) {
        Event::assertNotDispatched($otherClass);
    }
})->with([
    ['Document.Send.Created', DocumentSendCreated::class],
    ['Document.Send.Delivered', DocumentSendDelivered::class],
    ['Document.Send.Failed', DocumentSendFailed::class],
    ['Document.Receive.Created', DocumentReceiveCreated::class],
    ['Document.Receive.Ready', DocumentReceiveReady::class],
    ['Document.Receive.Delivered', DocumentReceiveDelivered::class],
]);

it('has a typed event for every webhook type the sdk knows', function () {
    foreach (WebhookEventType::cases() as $type) {
        expect(ProcessEcourierWebhookJob::typedEventClass($type))->not->toBeNull();
    }
});

/** @return list<class-string<DocumentWebhookEvent>> */
function typedEventClasses(): array
{
    return array_values(array_filter(array_map(
        fn (WebhookEventType $type): ?string => ProcessEcourierWebhookJob::typedEventClass($type),
        WebhookEventType::cases(),
    )));
}

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

it('records a failure on the webhook call and releases the event', function () {
    config(['cache.default' => 'array']);
    Cache::add('ecourier:webhook:evt_01hxyz', true, 60);

    (include dirname(__DIR__, 2).'/vendor/spatie/laravel-webhook-client/database/migrations/create_webhook_calls_table.php.stub')->up();

    $webhookCall = WebhookCall::create([
        'name' => 'ecourier',
        'url' => 'https://example.test/webhooks/ecourier',
        'headers' => [],
        'payload' => ['event_id' => 'evt_01hxyz'],
    ]);

    (new ProcessEcourierWebhookJob($webhookCall))->failed(new Error('Listener failed'));

    expect($webhookCall->fresh()->exception['message'])->toBe('Listener failed')
        ->and(Cache::has('ecourier:webhook:evt_01hxyz'))->toBeFalse();
});
