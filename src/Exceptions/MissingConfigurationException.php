<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Exceptions;

use RuntimeException;

class MissingConfigurationException extends RuntimeException
{
    public static function apiKey(): self
    {
        return new self('The eCourier API key is not set. Set ECOURIER_API_KEY in your .env file.');
    }

    public static function webhookSecret(): self
    {
        return new self('The eCourier webhook signing secret is not set. Set ECOURIER_WEBHOOK_SECRET in your .env file, or set ECOURIER_WEBHOOK_ENABLED=false if you don\'t receive webhooks.');
    }
}
