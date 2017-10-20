<?php

namespace Ttskch\Esa;

class WebhookValidator
{
    /**
     * @var string
     */
    private $secret;

    public function __construct($secret)
    {
        $this->secret = $secret;
    }

    /**
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    public function isValid($payload, $signature)
    {
        $hash = hash_hmac('sha256', $payload, $this->secret);

        return sprintf('sha256=%s', $hash) === $signature;
    }
}
