<?php

declare(strict_types=1);

use Ecourier\Laravel\EcourierServiceProvider;
use Ecourier\Laravel\Events\EcourierWebhookReceived;
use Ecourier\Laravel\Jobs\ProcessEcourierWebhookJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Spatie\WebhookClient\Models\WebhookCall;

beforeEach(function () {
    $this->app['config']->set('cache.default', 'array');
    $this->app['config']->set('ecourier.webhook.signing_secret', 'secret');
    $this->app->getProvider(EcourierServiceProvider::class)->packageBooted();

    (include dirname(__DIR__, 2).'/vendor/spatie/laravel-webhook-client/database/migrations/create_webhook_calls_table.php.stub')->up();

    Event::fake([EcourierWebhookReceived::class]);
});

function sendSignedWebhook(mixed $test): TestResponse
{
    $body = file_get_contents(__DIR__.'/../Fixtures/webhook-document.json');

    return $test->call('POST', 'webhooks/ecourier', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_SIGNATURE' => hash_hmac('sha256', $body, 'secret'),
    ], content: $body);
}

it('processes the first delivery of an event', function () {
    sendSignedWebhook($this)->assertOk();

    expect(WebhookCall::count())->toBe(1)
        ->and(Cache::has('ecourier:webhook:evt_01hxyz'))->toBeTrue();
    Event::assertDispatchedTimes(EcourierWebhookReceived::class, 1);
});

it('skips a repeated delivery of an event', function () {
    sendSignedWebhook($this)->assertOk();
    sendSignedWebhook($this)->assertOk();

    expect(WebhookCall::count())->toBe(1);
    Event::assertDispatchedTimes(EcourierWebhookReceived::class, 1);
});

it('processes an event again when its earlier job failed', function () {
    sendSignedWebhook($this)->assertOk();
    (new ProcessEcourierWebhookJob(WebhookCall::first()))->failed(new Exception('Listener failed'));

    sendSignedWebhook($this)->assertOk();

    expect(WebhookCall::count())->toBe(2);
    Event::assertDispatchedTimes(EcourierWebhookReceived::class, 2);
});

it('processes an event again once the ttl has passed', function () {
    $this->app['config']->set('ecourier.webhook.duplicate_ttl', 3600);

    sendSignedWebhook($this)->assertOk();
    $this->travel(3601)->seconds();
    sendSignedWebhook($this)->assertOk();

    expect(WebhookCall::count())->toBe(2);
});

it('processes repeated deliveries when skipping duplicates is off', function () {
    $this->app['config']->set('ecourier.webhook.skip_duplicates', false);

    sendSignedWebhook($this)->assertOk();
    sendSignedWebhook($this)->assertOk();

    expect(WebhookCall::count())->toBe(2)
        ->and(Cache::has('ecourier:webhook:evt_01hxyz'))->toBeFalse();
    Event::assertDispatchedTimes(EcourierWebhookReceived::class, 2);
});
