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

Each webhook fires an event for its type, so you can listen only for the ones you need:

```php
use Ecourier\Laravel\Events\Document\DocumentReceiveReady;

Event::listen(DocumentReceiveReady::class, function (DocumentReceiveReady $event) {
    $event->document; // Ecourier\Data\Responses\DocumentData
    $event->webhook; // Ecourier\Data\Responses\Webhook\DocumentWebhook, with eventId, mode, companyId and occurredAt
    $event->webhookCall; // Spatie webhook call model
});
```

| Webhook | Event |
|---|---|
| `Document.Send.Created` | `DocumentSendCreated` |
| `Document.Send.Delivered` | `DocumentSendDelivered` |
| `Document.Send.Failed` | `DocumentSendFailed` |
| `Document.Receive.Created` | `DocumentReceiveCreated` |
| `Document.Receive.Ready` | `DocumentReceiveReady` |
| `Document.Receive.Delivered` | `DocumentReceiveDelivered` |

The events are in the `Ecourier\Laravel\Events\Document` namespace and extend `DocumentWebhookEvent`, so `$event->webhook` is a `DocumentWebhook`. Laravel's event discovery picks up listeners that type-hint one of them.

Every webhook also fires `EcourierWebhookReceived`, before the typed event, if you'd rather handle all of them in one place:

```php
use Ecourier\Laravel\Events\EcourierWebhookReceived;

Event::listen(EcourierWebhookReceived::class, function (EcourierWebhookReceived $event) {
    $event->webhook; // Ecourier\Data\Responses\Webhook\WebhookEvent
    $event->webhook->event; // Ecourier\Enums\WebhookEventType
});
```

The webhook is processed on your queue. Some situations to know about:

- **Repeated deliveries are skipped.** eCourier retries a delivery if your app doesn't answer in time, and you can resend an event from the eCourier portal. When the job starts, it claims the webhook's `event_id` with `Cache::add()` in your default cache store, or in the store named by `ECOURIER_WEBHOOK_CACHE_STORE`. A later delivery of an event that was claimed in the last 24 hours is stored but skipped, and logged at info level. If the job fails, the claim is released, so a resend is processed again. Nothing is claimed until the job runs, so a delivery that fails to be stored or queued is processed when eCourier retries it. To process every delivery, set `ECOURIER_WEBHOOK_SKIP_DUPLICATES=false`.

  Which store you use decides whether the check is reliable:

  | Store | Duplicates skipped reliably? |
  |---|---|
  | `redis`, `memcached`, `database`, `dynamodb` | Yes. `Cache::add()` is atomic, so two copies arriving at once can't both get through. |
  | `file` | Mostly. It isn't atomic, so two copies arriving at the same moment can both be processed. |
  | `array` | No. It only lasts for one request, so nothing is remembered between deliveries. |

  If your default store is `file` or `array`, point the check at a shared store without changing your app's default:

  ```dotenv
  ECOURIER_WEBHOOK_CACHE_STORE=redis
  ECOURIER_WEBHOOK_DUPLICATE_TTL=86400 # seconds an event_id is remembered
  ```
- **Unknown event types are skipped.** If eCourier sends an event type that your version of `ecourier/ecourier` doesn't know, the webhook is logged as a warning and skipped, rather than failing the job. Update the SDK to receive it.
- **Failures are recorded.** When a listener throws and the job fails, the exception is saved on the webhook call.

## Credits

- [Bart Potmalnik](https://github.com/bpotmalnik) — original author
- [All Contributors](https://github.com/utecca/ecourier-laravel/graphs/contributors)

## License

MIT — see [LICENSE](LICENSE).
