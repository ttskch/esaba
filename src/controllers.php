<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Ttskch\AccessRestrictor;
use Ttskch\Esa\HtmlHandler;
use Ttskch\Esa\Proxy;
use Ttskch\AssetResolver;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function (Request $request) use ($app) {

    if ($postId = $request->get('post_id', null)) {
        return $app->redirect($app['url_generator']->generate('post', ['id' => $postId]));
    }

    return $app['twig']->render('index.html.twig');
})
->bind('homepage')
;


$app->get('/post/{id}/', function (Request $request, $id) use ($app) {

    $esa = $app['service.esa.proxy'];                   /** @var Proxy $esa */
    $restrictor = $app['service.access_restrictor'];    /** @var AccessRestrictor $restrictor */
    $htmlHandler = $app['service.esa.html_handler'];    /** @var HtmlHandler $htmlHandler */
    $assetResolver = $app['service.asset_resolver'];    /** @var AssetResolver $assetResolver */
    $force = boolval($request->get('force', 0));

    $post = $esa->getPost($id, $force);

    if (!$restrictor->isPublic($post['category'], $post['tags'])) {
        throw new NotFoundHttpException();
    }

    // fix boxy_html
    $htmlHandler->initialize($post['body_html']);
    $htmlHandler->replacePostUrls('post', 'id');
    $htmlHandler->disableMentionLinks();
    $htmlHandler->replaceEmojiCodes();
    $htmlHandler->replaceHtml($app['config.esa.html_replacements']);
    $post['body_html'] = $htmlHandler->dumpHtml();

    $toc = $htmlHandler->getToc();

    $assetPaths = $assetResolver->getAssetPaths($post['category'], $post['tags']);

    if ($force) {
        return $app->redirect($app['url_generator']->generate('post', ['id' => $id]));
    }

    return $app['twig']->render('post.html.twig', [
        'post' => $post,
        'toc' => $toc,
        'css' => $assetPaths['css'],
        'js' => $assetPaths['js'],
    ]);
})
->assert('id', '\d+')
->bind('post')
;

$app->get('/path/', function (Request $request) use ($app) {
    $path = $request->get('path', null);
    $page = $request->get('page', 1);
    $esa = $app['service.esa.proxy'];                   /** @var Proxy $esa */
    $restrictor = $app['service.access_restrictor'];    /** @var AccessRestrictor $restrictor */
    $result = $esa->getPosts(["q" => "in:$path", "page" => $page]);
    $posts = array_filter($result["posts"], function($post) use ($restrictor) {
      return $restrictor->isPublic($post['category'], $post['tags']);
    });

    $url_generator = $app['url_generator'];
    return $app['twig']->render('posts.html.twig', [
        'posts' => $posts,
        'path' => $path,
        'url_generator' => $url_generator,
        'prev_page' => $result['prev_page'],
        'next_page' => $result['next_page']
    ]);
})
->bind('posts')
;



$app->post('/webhook/', function (Request $request) use ($app) {

    $payload = $request->getContent();
    $signature = $request->headers->get('X-Esa-Signature');

    if ($signature && !$app['service.esa.webhook_validator']->isValid($payload, $signature)) {
        throw new NotFoundHttpException();
    }

    $body = json_decode($request->getContent(), true);

    switch ($body['kind']) {
        case 'post_create':
        case 'post_update':
            $app['service.esa.proxy']->getPost($body['post']['number'], true);
            if ($app['debug']) {
                $app['monolog']->debug(sprintf('Cache for post %d is warmed up!', $body['post']['number']));
            }
            break;
        default:
            break;
    }

    return new Response('OK');
})
->bind('webhook')
;


$app->error(function (\Exception $e, Request $request, $code) use ($app) {

    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = [
        'errors/'.$code.'.html.twig',
        'errors/'.substr($code, 0, 2).'x.html.twig',
        'errors/'.substr($code, 0, 1).'xx.html.twig',
        'errors/default.html.twig',
    ];

    return new Response($app['twig']->resolveTemplate($templates)->render(['code' => $code]), $code);
});
