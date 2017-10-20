<?php

namespace Ttskch\Esa;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

class EmojiManagerTest extends TestCase
{
    /**
     * @var EmojiManager
     */
    private $SUT;

    /**
     * @var ObjectProphecy
     */
    private $esa;

    /**
     * @var array
     */
    private $emojis;

    protected function setUp()
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
     *
     */
    public function testGetImageUrl($code, $expected)
    {
        $url = $this->SUT->getImageUrl($code);

        $this->assertEquals($expected, $url);
    }

    public function getImageUrlDataProvider()
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

    public function testUndefinedEmojiException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Undefined emoji code.');

        $this->SUT->getImageUrl('undefined_emoji');
    }

    public function testFlattenEmojis()
    {
        $flattened = $this->SUT->flattenEmojis($this->emojis);
        ksort($flattened);

        $this->assertEquals([
            'alias_to_code1_1' => 'url1',
            'alias_to_code1_2' => 'url1',
            'alias_to_code2_1' => 'url2',
            'alias_to_code2_2' => 'url2',
            'code1'            => 'url1',
            'code2'            => 'url2',
            'code3'            => 'url3',
        ], $flattened);
    }
}
