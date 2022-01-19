<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/', name: 'default_')]
class DefaultController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        if ($postId = $request->get('post_id')) {
            return $this->redirectToRoute('default_post', ['id' => $postId]);
        }

        return $this->render('default/index.html.twig');
    }

    #[Route('/{id}', name: 'post', requirements: ['id' => '\d+'])]
    public function post(string $id): Response
    {
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

        return $this->render('default/post.html.twig', [
            'post' => $post,
            'toc' => $toc,
            'css' => $assetPaths['css'],
            'js' => $assetPaths['js'],
        ]);
    }

    #[Route('/webhook', name: 'webhook')]
    public function webhook(Request $request): Response
    {
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
    }
}
