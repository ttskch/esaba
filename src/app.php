<?php

use Doctrine\Common\Cache\FilesystemCache;
use Polidog\Esa\Client;
use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\LocaleServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\DomCrawler\Crawler;
use Ttskch\AccessRestrictor;
use Ttskch\Esa\EmojiManager;
use Ttskch\Esa\Proxy;
use Ttskch\Esa\HtmlHandler;
use Ttskch\AssetResolver;

$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());

$app->register(new SessionServiceProvider());
$app->register(new FormServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new LocaleServiceProvider());
$app->register(new TranslationServiceProvider(), [
    'locale_fallbacks' => ['ja'],
]);

$app['twig'] = $app->extend('twig', function($twig, $app) {
    // add custom globals, filters, tags, ...

    return $twig;
});

$app->extend('translator', function($translator, $app) {
    /** @var \Symfony\Component\Translation\Translator $translator */
    $translator->addResource('xliff', __DIR__.'/../vendor/symfony/validator/Resources/translations/validators.ja.xlf', 'ja');

    return $translator;
});

// original services

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

$app['service.access_restrictor'] = $app->factory(function() use ($app) {
    return new AccessRestrictor($app['config.esa.public']['categories'], $app['config.esa.public']['tags'], $app['config.esa.private']['categories'], $app['config.esa.private']['tags']);
});

$app['service.asset_resolver'] = $app->factory(function() use ($app) {
    return new AssetResolver($app['config.esa.asset']);
});

return $app;
