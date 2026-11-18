<?php

declare(strict_types=1);

namespace Ecourier\Laravel\Webhooks;

use Ecourier\Laravel\Exceptions\MissingConfigurationException;
use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\DefaultSignatureValidator;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

/**
 * Spatie's default validator, but it names the .env key when the signing secret is missing.
 */
class EcourierSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        if (empty($config->signingSecret)) {
            throw MissingConfigurationException::webhookSecret();
        }

        return (new DefaultSignatureValidator)->isValid($request, $config);
    }
}
