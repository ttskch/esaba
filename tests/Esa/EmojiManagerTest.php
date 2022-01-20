<?php

declare(strict_types=1);

namespace App\Tests\Esa;

use App\Esa\EmojiManager;
use App\Esa\Exception\UndefinedEmojiException;
use App\Esa\Proxy;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class EmojiManagerTest extends TestCase
{
    use ProphecyTrait;

    private EmojiManager $SUT;
    private ObjectProphecy $esa;
    private array $emojis;

    protected function setUp(): void
    {
        $this->emojis = [
            [
                'code' => 'code1',
                'aliases' => [
                    'alias_to_code1_1',
                    'alias_to_code1_2',
                ],
                'url' => 'url1',
            ],
            [
                'code' => 'code2',
                'aliases' => [
                    'alias_to_code2_1',
                    'alias_to_code2_2',
                ],
                'url' => 'url2',
            ],
            [
                'code' => 'code3',
                'aliases' => [],
                'url' => 'url3',
            ],
        ];

        $this->esa = $this->prophesize(Proxy::class);
        $this->esa->getEmojis()->willReturn($this->emojis);

        $this->SUT = new EmojiManager($this->esa->reveal());
    }

    /**
     * @dataProvider getImageUrlDataProvider
     */
    public function testGetImageUrl(string $code, string $expected): void
    {
        $url = $this->SUT->getImageUrl($code);

        $this->assertEquals($expected, $url);
    }

    public function getImageUrlDataProvider(): array
    {
        return [
            ['code1', 'url1'],
            ['alias_to_code1_1', 'url1'],
            ['alias_to_code1_2', 'url1'],
            ['code2', 'url2'],
            ['alias_to_code2_1', 'url2'],
            ['alias_to_code2_2', 'url2'],
            ['code3', 'url3'],
        ];
    }

    public function testUndefinedEmojiException(): void
    {
        $this->expectException(UndefinedEmojiException::class);

        $this->SUT->getImageUrl('undefined_emoji');
    }

    public function testFlattenEmojis(): void
    {
        $reflection = new \ReflectionClass($this->SUT);
        $method = $reflection->getMethod('flattenEmojis');
        $method->setAccessible(true);

        $flattened = $method->invoke($this->SUT, $this->emojis);
        ksort($flattened);

        $this->assertEquals([
            'alias_to_code1_1' => 'url1',
            'alias_to_code1_2' => 'url1',
            'alias_to_code2_1' => 'url2',
            'alias_to_code2_2' => 'url2',
            'code1' => 'url1',
            'code2' => 'url2',
            'code3' => 'url3',
        ], $flattened);
    }
}
