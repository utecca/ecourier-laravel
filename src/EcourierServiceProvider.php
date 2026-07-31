<?php

declare(strict_types=1);

namespace Ecourier\Laravel;

use Ecourier\EcourierConnector;
use Ecourier\Laravel\Jobs\ProcessEcourierWebhookJob;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\WebhookClient\Exceptions\InvalidWebhookSignature;
use Spatie\WebhookClient\SignatureValidator\DefaultSignatureValidator;
use Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile;
use Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo;

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
        if (! config('ecourier.webhook.enabled')) {
            return;
        }

        $this->registerWebhookConfig();
        $this->registerWebhookRoute();
        $this->registerWebhookExceptionHandling();
    }

    private function registerWebhookConfig(): void
    {
        $configs = config('webhook-client.configs', []);
        $name = config('ecourier.webhook.name');

        // Drop any prior 'ecourier' entry (re-registration) and Spatie's unconfigured
        // 'default' placeholder, whose empty process_webhook_job crashes config resolution.
        $configs = array_values(array_filter(
            $configs,
            fn (array $config): bool => ($config['name'] ?? null) !== $name
                && ! empty($config['process_webhook_job'] ?? null),
        ));

        $configs[] = [
            'name' => $name,
            'signing_secret' => config('ecourier.webhook.signing_secret'),
            'signature_header_name' => 'Signature',
            'signature_validator' => DefaultSignatureValidator::class,
            'webhook_profile' => ProcessEverythingWebhookProfile::class,
            'webhook_response' => DefaultRespondsTo::class,
            'webhook_model' => config('ecourier.webhook.webhook_model'),
            'store_headers' => [],
            'process_webhook_job' => ProcessEcourierWebhookJob::class,
        ];

        config(['webhook-client.configs' => $configs]);
    }

    private function registerWebhookRoute(): void
    {
        $path = config('ecourier.webhook.path');
        $domain = config('ecourier.webhook.domain');

        if (! $path || $this->app->routesAreCached()) {
            return;
        }

        $registerRoute = fn () => Route::webhooks($path, config('ecourier.webhook.name'));

        if ($domain) {
            Route::domain($domain)->group($registerRoute);
        } else {
            $registerRoute();
        }
    }

    private function registerWebhookExceptionHandling(): void
    {
        $this->app->make(ExceptionHandler::class)->renderable(
            fn (InvalidWebhookSignature $exception) => response()->json([
                'message' => 'Invalid webhook signature.',
            ], 400)
        );
    }
}
