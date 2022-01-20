<?php

declare(strict_types=1);

namespace App\Tests\Esa;

use App\Esa\WebhookValidator;
use PHPUnit\Framework\TestCase;

class WebhookValidatorTest extends TestCase
{
    private WebhookValidator $SUT;

    protected function setUp(): void
    {
        $this->SUT = new WebhookValidator('secret');
    }

    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(string $payload, string $signature, bool $expected): void
    {
        $this->assertEquals($expected, $this->SUT->isValid($payload, $signature));
    }

    public function isValidDataProvider(): array
    {
        return [
            ['payload1', 'sha256=7eaccb2dbb742b50f2cf242a05f633cd81f3a608300191c12d42ce71199a9c6d', true],
            ['payload2', 'sha256=0b50c227b58efac43baa98fd2b6e24bbf5c2ae6961d8fe1d854e72d1dfc17cd6', true],
            ['payload3', 'sha256=0791729019c2ffd3213f864b4b0b66461bbcbb2b94e6884a31dfd80f4f0a049b', true],
            ['payload4', 'sha256=0791729019c2ffd3213f864b4b0b66461bbcbb2b94e6884a31dfd80f4f0a049b', false],
        ];
    }
}
