<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html.twig');
})
->bind('homepage')
;


$app->get('/post/{id}', function (Request $request, $id) use ($app) {
    $force = boolval($request->get('force', 0));

    $post = $app['service.esa']->getPost($id, $force);

    $toc = $app['service.html_helper']->getToc($post['body_html']);
    $post['body_html'] = $app['service.html_helper']->replace($post['body_html'], 'post', 'id');

    if (!$app['service.category_checker']->check($post['category'])) {
        throw new NotFoundHttpException();
    }

    if ($force) {
        return $app->redirect($app['url_generator']->generate('post', ['id' => $id]));
    }

    return $app['twig']->render('post.html.twig', [
        'post' => $post,
        'toc' => $toc,
        'team_name' => $app['esa.team_name'],
    ]);
})
->assert('id', '\d+')
->bind('post')
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
