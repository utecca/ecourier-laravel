<?php

declare(strict_types=1);

namespace Ecourier\Laravel;

use Ecourier\Sdk\EcourierConnector;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class EcourierServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ecourier')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(EcourierConnector::class, fn (): EcourierConnector => new EcourierConnector(
            apiKey: (string) config('ecourier.api_key', ''),
        ));
    }

    public function packageBooted(): void
    {
        if (! config('ecourier.webhooks.enabled')) {
            return;
        }

        $this->registerWebhookConfig();
        $this->registerWebhookRoute();
    }

    private function registerWebhookConfig(): void
    {
        $configs = config('webhook-client.configs', []);
        $name = config('ecourier.webhooks.name');

        $configs = array_values(array_filter(
            $configs,
            fn (array $config): bool => ($config['name'] ?? null) !== $name,
        ));

        $configs[] = Arr::except(config('ecourier.webhooks'), ['enabled', 'path']);

        config(['webhook-client.configs' => $configs]);
    }

    private function registerWebhookRoute(): void
    {
        $path = config('ecourier.webhooks.path');

        if (! $path || $this->app->routesAreCached()) {
            return;
        }

        Route::webhooks($path, config('ecourier.webhooks.name'));
    }
}
