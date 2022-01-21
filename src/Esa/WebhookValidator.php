<?php

declare(strict_types=1);

namespace App\Esa;

class WebhookValidator
{
    public function __construct(private string $secret)
    {
    }

    public function isValid(string $payload, string $signature): bool
    {
        $hash = hash_hmac('sha256', $payload, $this->secret);

        return sprintf('sha256=%s', $hash) === $signature;
    }
}
