# eCourier Laravel

Laravel wrapper for `ecourier/ecourier`.

## Installation

```bash
composer require ecourier/ecourier-laravel
php artisan vendor:publish --tag=ecourier-config
```

Version 2 is in beta and requires `ecourier/ecourier` v2. To try it, require both explicitly:

```bash
composer require ecourier/ecourier-laravel:^2.0@beta ecourier/ecourier:^2.0@beta
```

## Usage

```php
use Ecourier\EcourierConnector;

$document = app(EcourierConnector::class)->documents()->find('doc_01xyz');
```

## Config

Set your API key in `.env`:

```dotenv
ECOURIER_API_KEY=pk_test_your_key
```

If the API key is missing, resolving `EcourierConnector` throws a `MissingConfigurationException`.

## Webhooks

Incoming webhooks are registered at `/webhooks/ecourier` by default and are handled by `spatie/laravel-webhook-client`, which stores each call in a `webhook_calls` table. Publish its migration and run it:

```bash
php artisan vendor:publish --provider="Spatie\WebhookClient\WebhookClientServiceProvider" --tag="webhook-client-migrations"
php artisan migrate
```

Set the signing secret from your eCourier webhook settings. Webhooks are rejected with a `MissingConfigurationException` until it's set. If you don't receive webhooks, set `ECOURIER_WEBHOOK_ENABLED=false` instead.

```dotenv
ECOURIER_WEBHOOK_SECRET=your_webhook_secret
ECOURIER_WEBHOOK_MODEL="App\Models\WebhookCall"
```

Listen for parsed webhook events:

```php
use Ecourier\Laravel\Events\EcourierWebhookReceived;

Event::listen(EcourierWebhookReceived::class, function (EcourierWebhookReceived $event) {
    $event->webhook; // Ecourier\Data\Responses\Webhook\DocumentWebhook
    $event->webhookCall; // Spatie webhook call model
});
```

The webhook is processed on your queue. Some situations to know about:

- **Repeated deliveries are skipped.** eCourier retries a delivery if your app doesn't answer in time, and you can resend an event from the dashboard. A webhook whose `event_id` was already received is skipped, unless the earlier call failed. To process every delivery, set `ECOURIER_WEBHOOK_SKIP_DUPLICATES=false`.
- **Unknown event types are skipped.** If eCourier sends an event type that your version of `ecourier/ecourier` doesn't know, the webhook is logged as a warning and skipped, rather than failing the job. Update the SDK to receive it.
- **Failures are recorded.** When a listener throws and the job fails, the exception is saved on the webhook call, so a resend of that event is processed again.

## Credits

- [Bart Potmalnik](https://github.com/bpotmalnik) — original author
- [All Contributors](https://github.com/utecca/ecourier-laravel/graphs/contributors)

## License

MIT — see [LICENSE](LICENSE).
