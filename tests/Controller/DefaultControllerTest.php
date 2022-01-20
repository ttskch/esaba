<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Esa\Proxy;
use App\Service\AccessController;
use Polidog\Esa\Api;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Contracts\Cache\CacheInterface;

class DefaultControllerTest extends WebTestCase
{
    use ProphecyTrait;

    public function testIndex()
    {
        $client = self::createClient();

        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('esaba', $crawler->filter('h1')->text(null, true));

        $client->request('GET', '/', ['post_id' => 1]);
        $this->assertResponseRedirects('/post/1');
    }

    public function testPost()
    {
        $client = self::createClient();
        $client->disableReboot();
        $container = self::getContainer();

        $container->set(AccessController::class, new AccessController([], [], [], ['private']));

        $api = $this->prophesize(Api::class);

        $posts = [
            1 => json_decode(trim(file_get_contents(__DIR__.'/../fixtures/post/post.1.json')), true),
            2 => json_decode(trim(file_get_contents(__DIR__.'/../fixtures/post/post.2.json')), true), // tagged as private
        ];

        $api->post(1)->willReturn($posts[1]);
        $api->post(2)->willReturn($posts[2]);

        $emojis = json_decode(trim(file_get_contents(__DIR__.'/../fixtures/post/emojis.json')), true);
        $api->emojis(Argument::cetera())->willReturn($emojis);

        $esa = new Proxy($api->reveal(), $container->get(CacheInterface::class));

        $container->set(Proxy::class, $esa);

        // public post
        $crawler = $client->request('GET', '/post/1');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Getting Started', $crawler->filter('#esa-content')->text(null, true));

        // private post
        $client->request('GET', '/post/2');
        $this->assertResponseStatusCodeSame(404);

        // force get
        $client->request('GET', '/post/1?force=1');
        $this->assertResponseRedirects('/post/1');
    }

    /**
     * @see https://docs.esa.io/posts/37
     */
    public function testWebhook()
    {
        $client = self::createClient();
        $client->disableReboot();
        $container = self::getContainer();

        $payloads['post_create'] = trim(file_get_contents(__DIR__.'/../fixtures/webhook/payload.post_create.json'));
        $payloads['post_update'] = trim(file_get_contents(__DIR__.'/../fixtures/webhook/payload.post_update.json'));
        $signatures['post_create'] = trim(file_get_contents(__DIR__.'/../fixtures/webhook/signature.post_create.txt'));
        $signatures['post_update'] = trim(file_get_contents(__DIR__.'/../fixtures/webhook/signature.post_update.txt'));

        $api = $this->prophesize(Api::class);
        $api->post(1253)->shouldBeCalledTimes(3);
        $esa = new Proxy($api->reveal(), $container->get(CacheInterface::class));
        $container->set(Proxy::class, $esa);

        $client->request('POST', '/webhook', [], [], ['HTTP_X-Esa-Signature' => $signatures['post_create']], $payloads['post_create']);
        $this->assertResponseIsSuccessful();

        $client->request('POST', '/webhook', [], [], ['HTTP_X-Esa-Signature' => $signatures['post_update']], $payloads['post_update']);
        $this->assertResponseIsSuccessful();

        $client->request('POST', '/webhook', [], [], [], $payloads['post_update']);
        $this->assertResponseIsSuccessful();

        $client->request('POST', '/webhook', [], [], ['HTTP_X-Esa-Signature' => $signatures['post_create']], 'invalid_payload');
        $this->assertResponseStatusCodeSame(404);

        $client->request('POST', '/webhook', [], [], [], '{"kind":"other"}');
        $this->assertResponseIsSuccessful();
    }

    protected static function createClient(array $options = [], array $server = []): KernelBrowser
    {
        static::ensureKernelShutdown();

        return parent::createClient($options, $server);
    }
}
