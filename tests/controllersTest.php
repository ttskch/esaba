<?php

use Prophecy\Argument;
use Silex\WebTestCase;
use Ttskch\AccessRestrictor;
use Ttskch\AssetResolver;
use Ttskch\Esa\HtmlHandler;
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
        $this->assertTrue($client->getResponse()->isRedirect('/post/1'));
    }

    public function testGetPost()
    {
        $client = $this->createClient();

        $this->mockOriginalServices();

        // public post
        $crawler = $client->request('GET', '/post/1');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertContains('esa-content', $crawler->filter('#esa-content')->text());

        // private post
        $client->request('GET', '/post/2');
        $this->assertTrue($client->getResponse()->isClientError());

        // force get
        $client->request('GET', '/post/1?force=1');
        $this->assertTrue($client->getResponse()->isRedirect('/post/1'));
    }

    private function mockOriginalServices()
    {
        $esa = $this->prophesize(Proxy::class);
        $esa->getPost(1, Argument::cetera())->willReturn([
            'number' => 1,
            'full_name' => 'full_name',
            'name' => 'name',
            'updated_at' => '2000-01-01 00:00:00',
            'wip' => false,
            'url' => 'url',
            'body_html' => 'body_html',
            'category' => 'public',
            'tags' => [],
        ]);
        $esa->getPost(2, Argument::cetera())->willReturn([
            'number' => 2,
            'full_name' => 'full_name',
            'name' => 'name',
            'updated_at' => '2000-01-01 00:00:00',
            'wip' => false,
            'url' => 'url',
            'body_html' => 'body_html',
            'category' => 'private',
            'tags' => [],
        ]);

        $restrictor = $this->prophesize(AccessRestrictor::class);
        $restrictor->isPublic('public', Argument::cetera())->willReturn(true);
        $restrictor->isPublic('private', Argument::cetera())->willReturn(false);

        $htmlHandler = $this->prophesize(HtmlHandler::class);
        $htmlHandler->initialize(Argument::cetera())->shouldBeCalled();
        $htmlHandler->replacePostUrls(Argument::cetera())->shouldBeCalled();
        $htmlHandler->disableMentionLinks()->shouldBeCalled();
        $htmlHandler->replaceEmojiCodes()->shouldBeCalled();
        $htmlHandler->replaceHtml(Argument::cetera())->shouldBeCalled();
        $htmlHandler->dumpHtml(Argument::cetera())->willReturn('<p>esa-content</p>');
        $htmlHandler->getToc()->willReturn([]);

        $assetResolver = $this->prophesize(AssetResolver::class);
        $assetResolver->getAssetPaths(Argument::cetera())->willReturn([
            'css' => 'css/post/default.css',
            'js' => 'js/post/default.js',
        ]);

        $this->app['service.esa.proxy'] = $esa->reveal();
        $this->app['service.access_restrictor'] = $restrictor->reveal();
        $this->app['service.esa.html_handler'] = $htmlHandler->reveal();
        $this->app['service.asset_resolver'] = $assetResolver->reveal();
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
        require __DIR__.'/../config/config.php';
        require __DIR__.'/../config/dev.php';
        require __DIR__.'/../src/controllers.php';
        $app['session.test'] = true;

        return $this->app = $app;
    }
}
