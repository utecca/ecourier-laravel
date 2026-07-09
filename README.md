# eCourier Laravel

Laravel wrapper for `ecourier/sdk`.

## Installation

```bash
composer require utecca/ecourier-laravel
php artisan vendor:publish --tag=ecourier-config
```

## Usage

```php
use Ecourier\Sdk\EcourierConnector;

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
    $event->webhook; // Ecourier\Sdk\Data\Webhook\DocumentWebhook
    $event->webhookCall; // Spatie webhook call model
});
```
