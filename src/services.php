<?php

use Doctrine\Common\Cache\FilesystemCache;
use Polidog\Esa\Client;
use Symfony\Component\DomCrawler\Crawler;
use Ttskch\AccessRestrictor;
use Ttskch\Esa\EmojiManager;
use Ttskch\Esa\Proxy;
use Ttskch\Esa\HtmlHandler;
use Ttskch\AssetResolver;
use Ttskch\Esa\WebhookValidator;

$app['service.esa.proxy'] = $app->factory(function() use ($app) {
    $client = new Client($app['config.esa.access_token'], $app['config.esa.team_name']);
    $cache = new FilesystemCache(__DIR__.'/../var/cache/esa');

    return new Proxy($client, $cache);
});

$app['service.esa.html_handler'] = $app->factory(function() use ($app) {
    $crawler = new Crawler();

    return new HtmlHandler($crawler, $app['url_generator'], $app['service.esa.emoji_manager'], $app['config.esa.team_name']);
});

$app['service.esa.emoji_manager'] = $app->factory(function() use ($app) {
    return new EmojiManager($app['service.esa.proxy']);
});

$app['service.esa.webhook_validator'] = $app->factory(function() use ($app) {
    return new WebhookValidator($app['config.esa.webhook_secret']);
});

$app['service.access_restrictor'] = $app->factory(function() use ($app) {
    return new AccessRestrictor($app['config.esa.public']['categories'], $app['config.esa.public']['tags'], $app['config.esa.private']['categories'], $app['config.esa.private']['tags']);
});

$app['service.asset_resolver'] = $app->factory(function() use ($app) {
    return new AssetResolver($app['config.esa.asset']);
});
