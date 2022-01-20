<?php

declare(strict_types=1);

namespace App\Tests\Esa;

use App\Esa\Proxy;
use PHPUnit\Framework\TestCase;
use Polidog\Esa\Api;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Contracts\Cache\CacheInterface;

class ProxyTest extends TestCase
{
    use ProphecyTrait;

    public function testGetPost(): void
    {
        $api = $this->prophesize(Api::class);

        $cache = $this->prophesize(CacheInterface::class);
        $cache->delete(Argument::type('string'))->shouldNotBeCalled(); // force: false
        $cache->get(Argument::type('string'), Argument::type('callable'))->shouldBeCalled()->willReturn(['post']);

        $SUT = new Proxy($api->reveal(), $cache->reveal());
        $SUT->getPost(1);

        $cache = $this->prophesize(CacheInterface::class);
        $cache->delete(Argument::type('string'))->shouldBeCalled(); // force: true
        $cache->get(Argument::type('string'), Argument::type('callable'))->shouldBeCalled()->willReturn(['post']);

        $SUT = new Proxy($api->reveal(), $cache->reveal());
        $SUT->getPost(1, true);
    }

    public function testGetEmojis()
    {
        $api = $this->prophesize(Api::class);

        $cache = $this->prophesize(CacheInterface::class);
        $cache->get(Argument::type('string'), Argument::type('callable'))->shouldBeCalled()->willReturn(['emojis']);

        $SUT = new Proxy($api->reveal(), $cache->reveal());
        $SUT->getEmojis();
    }
}
