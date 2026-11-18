<?php

declare(strict_types=1);

namespace Ecourier\Laravel;

use Ecourier\EcourierConnector;
use Ecourier\Laravel\Exceptions\MissingConfigurationException;
use Ecourier\Laravel\Jobs\ProcessEcourierWebhookJob;
use Ecourier\Laravel\Webhooks\EcourierSignatureValidator;
use Ecourier\Laravel\Webhooks\SkipDuplicateWebhooks;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\WebhookClient\Exceptions\InvalidWebhookSignature;
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
        $this->app->singleton(EcourierConnector::class, function (): EcourierConnector {
            $apiKey = config('ecourier.api_key');

            if (! is_string($apiKey) || $apiKey === '') {
                throw MissingConfigurationException::apiKey();
            }

            return new EcourierConnector(apiKey: $apiKey);
        });
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
        $configs = config()->array('webhook-client.configs', []);
        $name = config()->string('ecourier.webhook.name');

        // Drop any prior 'ecourier' entry (re-registration) and Spatie's unconfigured
        // 'default' placeholder, whose empty process_webhook_job crashes config resolution.
        $configs = array_values(array_filter(
            $configs,
            fn (mixed $config): bool => is_array($config)
                && ($config['name'] ?? null) !== $name
                && ! empty($config['process_webhook_job'] ?? null),
        ));

        $configs[] = [
            'name' => $name,
            'signing_secret' => config('ecourier.webhook.signing_secret'),
            'signature_header_name' => 'Signature',
            'signature_validator' => EcourierSignatureValidator::class,
            'webhook_profile' => SkipDuplicateWebhooks::class,
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
        $name = config()->string('ecourier.webhook.name');

        if (! is_string($path) || $path === '' || $this->app->routesAreCached()) {
            return;
        }

        $registerRoute = fn () => Route::webhooks($path, $name);

        if (is_string($domain) && $domain !== '') {
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
