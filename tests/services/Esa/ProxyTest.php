<?php

namespace Ttskch\Esa;

use Doctrine\Common\Cache\Cache;
use PHPUnit\Framework\TestCase;
use Polidog\Esa\Api;
use Prophecy\Argument;

class ProxyTest extends TestCase
{
    /**
     * @var Proxy
     */
    private $SUT;

    /**
     * @dataProvider getPostDataProvider
     */
    public function testGetPost($force, $cacheExists, $expected)
    {
        $cache = $this->prophesize(Cache::class);

        if (!$force) {
            $cache->fetch(Argument::type('string'))->shouldBeCalled()->willReturn($cacheExists ? ['cached_data'] : false);
        }

        if ($force || !$cacheExists) {
            $cache->save(Argument::type('string'), ['new_data'])->shouldBeCalled();
        }

        $api = $this->prophesize(Api::class);
        $api->post(Argument::type('int'))->willReturn(['new_data']);

        $this->SUT = new Proxy($api->reveal(), $cache->reveal());

        $post = $this->SUT->getPost(1, $force);

        $this->assertEquals($expected, $post);
    }

    public function getPostDataProvider()
    {
        return [
            [true,  true,  ['new_data']],
            [true,  false, ['new_data']],
            [false, true,  ['cached_data']],
            [false, false, ['new_data']],
        ];
    }

    /**
     * @dataProvider getEmojisDataProvider
     */
    public function testGetEmojis($cacheExists, $expected)
    {
        $cache = $this->prophesize(Cache::class);

        $cache->fetch(Argument::type('string'))->willReturn($cacheExists ? ['cached_data'] : false);

        if (!$cacheExists) {
            $cache->save(Argument::type('string'), ['new_data'])->shouldBeCalled();
        }

        $api = $this->prophesize(Api::class);
        $api->emojis(['include' => 'all'])->willReturn(['emojis' => ['new_data']]);

        $this->SUT = new Proxy($api->reveal(), $cache->reveal());

        $emojis = $this->SUT->getEmojis();

        $this->assertEquals($expected, $emojis);
    }

    public function getEmojisDataProvider()
    {
        return [
            [true,  ['cached_data']],
            [false, ['new_data']],
        ];
    }

}
