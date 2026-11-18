<?php

declare(strict_types=1);

use Ecourier\Laravel\EcourierServiceProvider;
use Ecourier\Laravel\Events\EcourierWebhookReceived;
use Ecourier\Laravel\Jobs\ProcessEcourierWebhookJob;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Illuminate\Testing\TestResponse;
use Spatie\WebhookClient\Models\WebhookCall;

beforeEach(function () {
    $this->app['config']->set('ecourier.webhook.signing_secret', 'secret');
    $this->app->getProvider(EcourierServiceProvider::class)->packageBooted();

    (include dirname(__DIR__, 2).'/vendor/spatie/laravel-webhook-client/database/migrations/create_webhook_calls_table.php.stub')->up();

    Event::fake([EcourierWebhookReceived::class]);

    // An in-memory stand-in for Redis SET NX EX and DEL.
    $this->redisKeys = new ArrayObject;
    $this->redisTtls = new ArrayObject;
    $keys = $this->redisKeys;
    $ttls = $this->redisTtls;

    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('set')->andReturnUsing(function (string $key, string $value, string $ex, int $ttl, string $nx) use ($keys, $ttls): bool {
        if (isset($keys[$key])) {
            return false;
        }

        $keys[$key] = $value;
        $ttls[$key] = $ttl;

        return true;
    });
    $connection->shouldReceive('del')->andReturnUsing(function (string $key) use ($keys): int {
        unset($keys[$key]);

        return 1;
    });

    Redis::shouldReceive('connection')->with('default')->andReturn($connection);
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
        ->and($this->redisKeys->getArrayCopy())->toHaveKey('ecourier:webhook:evt_01hxyz')
        ->and($this->redisTtls['ecourier:webhook:evt_01hxyz'])->toBe(86400);
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

it('uses the configured ttl', function () {
    $this->app['config']->set('ecourier.webhook.duplicate_ttl', 3600);

    sendSignedWebhook($this)->assertOk();

    expect($this->redisTtls['ecourier:webhook:evt_01hxyz'])->toBe(3600);
});

it('processes repeated deliveries without redis when skipping duplicates is off', function () {
    $this->app['config']->set('ecourier.webhook.skip_duplicates', false);

    sendSignedWebhook($this)->assertOk();
    sendSignedWebhook($this)->assertOk();

    expect(WebhookCall::count())->toBe(2)
        ->and($this->redisKeys->count())->toBe(0);
    Event::assertDispatchedTimes(EcourierWebhookReceived::class, 2);
});
