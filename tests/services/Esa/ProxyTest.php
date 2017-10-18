<?php

namespace Ttskch\Esa;

use Doctrine\Common\Cache\Cache;
use PHPUnit\Framework\TestCase;
use Polidog\Esa\Client;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class ProxyTest extends TestCase
{
    /**
     * @var Proxy
     */
    private $SUT;

    /**
     * @dataProvider getPostDataProvider
     * @group tmp
     */
    public function testGetPost($force, $cacheExists, $expected)
    {
        $cache = $this->prophesize(Cache::class);

        if (!$force) {
            $cache->fetch(Argument::type('string'))->willReturn($cacheExists ? ['post' => 'cached_data'] : false);
        }

        if ($force || !$cacheExists) {
            $client = $this->getFakePolidogEsaClient(json_encode(['post' => 'new_data']));
            $cache->save(Argument::type('string'), ['post' => 'new_data'])->shouldBeCalled();
        } else {
            $client = $this->getFakePolidogEsaClient('');
        }

        $this->SUT = new Proxy($client, $cache->reveal());

        $post = $this->SUT->getPost(1, $force);

        $this->assertEquals($expected, $post);
    }

    public function getPostDataProvider()
    {
        return [
            [true,  true,  ['post' => 'new_data']],
            [true,  false, ['post' => 'new_data']],
            [false, true,  ['post' => 'cached_data']],
            [false, false, ['post' => 'new_data']],
        ];
    }

    // \Polidog\Esa\Client is marked as final and cannot be mocked...
    private function getFakePolidogEsaClient($json)
    {
        $httpClient = $this->prophesize(\GuzzleHttp\Client::class);
        $response = $this->prophesize(\Psr\Http\Message\ResponseInterface::class);
        $responseBody = $this->prophesize(\Psr\Http\Message\StreamInterface::class);

        // mock get-post api
        $httpClient->request(Argument::that(function($v) {
            return strtolower($v) === 'get';
        }), Argument::that(function($v) {
            return preg_match('#teams/team_name/posts/\d+#', $v);
        }))->willReturn($response->reveal());

        // mock get-emojis api
        $httpClient->request(Argument::that(function($v) {
            return strtolower($v) === 'get';
        }), Argument::that(function($v) {
            return preg_match('#teams/team_name/emojis#', $v);
        }), Argument::type('array'))->willReturn($response->reveal());

        $response->getStatusCode()->willReturn(200);
        $response->getBody()->willReturn($responseBody->reveal());

        $responseBody->getContents()->willReturn($json);

        return new Client('access_token', 'team_name', $httpClient->reveal());
    }
}
