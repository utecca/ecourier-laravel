# eCourier Laravel

Laravel wrapper for `ecourier/ecourier`.

## Installation

```bash
composer require ecourier/ecourier-laravel
php artisan vendor:publish --tag=ecourier-config
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

Incoming webhooks are registered at `/webhooks/ecourier` by default and are handled by `spatie/laravel-webhook-client`.

```dotenv
ECOURIER_WEBHOOK_SECRET=your_webhook_secret
ECOURIER_WEBHOOK_MODEL="App\Models\WebhookCall"
```

Listen for parsed webhook events:

```php
use Ecourier\Laravel\Events\EcourierWebhookReceived;

Event::listen(EcourierWebhookReceived::class, function (EcourierWebhookReceived $event) {
    $event->webhook; // Ecourier\Data\Webhook\DocumentWebhook
    $event->webhookCall; // Spatie webhook call model
});
```

## Credits

- [Bart Potmalnik](https://github.com/bpotmalnik) — original author
- [All Contributors](https://github.com/utecca/ecourier-laravel/graphs/contributors)

## License

MIT — see [LICENSE](LICENSE).
