<?php

use Prophecy\Argument;
use Silex\WebTestCase;
use Ttskch\Esa\Proxy;

class controllersTest extends WebTestCase
{
    public function testGetHomepage()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertContains('esaba', $crawler->filter('h1')->text());

        $client->request('GET', '/', ['post_id' => 1]);
        $this->assertTrue($client->getResponse()->isRedirect('/post/1/'));
    }

    /**
     * @see https://docs.esa.io/posts/37
     */
    public function testGetPost()
    {
        $this->app['config.esa.private'] = [
            'categories' => [],
            'tags' => ['private'],
        ];

        $esa = $this->prophesize(Proxy::class);

        $posts = [
            1 => json_decode(trim(file_get_contents(__DIR__.'/fixtures/post/post.1.json')), true),
            2 => json_decode(trim(file_get_contents(__DIR__.'/fixtures/post/post.2.json')), true),
        ];
        $esa->getPost(1, Argument::cetera())->willReturn($posts[1]);
        $esa->getPost(2, Argument::cetera())->willReturn($posts[2]);

        $emojis = json_decode(trim(file_get_contents(__DIR__.'/fixtures/post/emojis.json')), true)['emojis'];
        $esa->getEmojis()->willReturn($emojis);

        $this->app['service.esa.proxy'] = $esa->reveal();

        $client = $this->createClient();

        // public post
        $crawler = $client->request('GET', '/post/1/');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertContains('Getting Started', $crawler->filter('#esa-content')->text());

        // private post
        $client->request('GET', '/post/2/');
        $this->assertTrue($client->getResponse()->isClientError());

        // force get
        $client->request('GET', '/post/1/?force=1');
        $this->assertTrue($client->getResponse()->isRedirect('/post/1/'));
    }

    /**
     * @see https://docs.esa.io/posts/37
     */
    public function testWebhook()
    {
        $payloads['post_create'] = trim(file_get_contents(__DIR__.'/fixtures/webhook/payload.post_create.json'));
        $payloads['post_update'] = trim(file_get_contents(__DIR__.'/fixtures/webhook/payload.post_update.json'));
        $signatures['post_create'] = trim(file_get_contents(__DIR__.'/fixtures/webhook/signature.post_create.txt'));
        $signatures['post_update'] = trim(file_get_contents(__DIR__.'/fixtures/webhook/signature.post_update.txt'));

        $esa = $this->prophesize(Proxy::class);
        $esa->getPost(1253, true)->shouldBeCalledTimes(3);
        $this->app['service.esa.proxy'] = $esa->reveal();

        $client = $this->createClient();

        $client->request('POST', '/webhook/', [], [], ['HTTP_X-Esa-Signature' => $signatures['post_create']], $payloads['post_create']);
        $this->assertTrue($client->getResponse()->isOk());

        $client->request('POST', '/webhook/', [], [], ['HTTP_X-Esa-Signature' => $signatures['post_update']], $payloads['post_update']);
        $this->assertTrue($client->getResponse()->isOk());

        $client->request('POST', '/webhook/', [], [], [], $payloads['post_update']);
        $this->assertTrue($client->getResponse()->isOk());

        $client->request('POST', '/webhook/', [], [], ['HTTP_X-Esa-Signature' => $signatures['post_create']], 'invalid_payload');
        $this->assertTrue($client->getResponse()->isClientError());

        $client->request('POST', '/webhook/', [], [], [], '{"kind":"other"}');
        $this->assertTrue($client->getResponse()->isOk());
    }

    public function testError()
    {
        $client = $this->createClient();

        $crawler = $client->request('GET', '/undefined/route');
        $this->assertTrue($client->getResponse()->isClientError());

        $this->app['debug'] = false;
        $crawler = $client->request('GET', '/undefined/route');
        $this->assertTrue($client->getResponse()->isClientError());
    }

    public function createApplication()
    {
        $app = require __DIR__.'/../src/app.php';
        require __DIR__.'/../src/services.php';
        require __DIR__.'/../config/config.php';
        require __DIR__.'/../config/dev.php';
        require __DIR__.'/../src/controllers.php';
        $app['session.test'] = true;

        $app['config.esa.team_name'] = 'test';
        $app['config.esa.access_token'] = 'token';
        $app['config.esa.webhook_secret'] = 'secret';

        return $this->app = $app;
    }
}
