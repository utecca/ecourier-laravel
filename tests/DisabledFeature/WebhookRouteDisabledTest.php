<?php

declare(strict_types=1);

use Ecourier\Laravel\Tests\DisabledWebhooksTestCase;
use Illuminate\Support\Facades\Route;

uses(DisabledWebhooksTestCase::class);

it('can disable the built-in webhook route', function () {
    expect(Route::has('webhook-client-ecourier'))->toBeFalse()
        ->and(collect(config('webhook-client.configs'))->firstWhere('name', 'ecourier'))->toBeNull();
});
