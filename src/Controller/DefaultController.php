<?php

declare(strict_types=1);

namespace App\Controller;

use App\Esa\HtmlHandler;
use App\Esa\Proxy;
use App\Esa\WebhookValidator;
use App\Service\AccessController;
use App\Service\AssetResolver;
use Polidog\Esa\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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

    #[Route('/post/{id}', name: 'post', requirements: ['id' => '\d+'])]
    public function post(
        Request $request,
        int $id,
        Proxy $esa,
        AccessController $accessController,
        HtmlHandler $htmlHandler,
        AssetResolver $assetResolver,
        array $htmlReplacements,
    ): Response {
        $force = boolval($request->get('force', 0));

        try {
            $post = $esa->getPost($id, $force);
        } catch (ClientException $e) {
            throw new NotFoundHttpException();
        }

        if (!$accessController->isPublic($post['category'], $post['tags'])) {
            throw new NotFoundHttpException();
        }

        // fix body_html
        $htmlHandler
            ->initialize($post['body_html'])
            ->replacePostUrls('post', 'id')
            ->disableMentionLinks()
            ->replaceEmojiCodes()
            ->replaceHtml($htmlReplacements)
            ->dumpHtml()
        ;
        $post['body_html'] = $htmlHandler->dumpHtml();
        $toc = $htmlHandler->getToc();

        $assetPaths = $assetResolver->getAssetPaths($post['category'], $post['tags']);

        if ($force) {
            return $this->redirectToRoute('default_post', ['id' => $id]);
        }

        return $this->render('default/post.html.twig', [
            'post' => $post,
            'toc' => $toc,
            'css' => $assetPaths['css'],
            'js' => $assetPaths['js'],
        ]);
    }

    #[Route('/webhook', name: 'webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        WebhookValidator $validator,
        Proxy $esa,
        LoggerInterface $logger
    ): Response {
        $payload = $request->getContent();
        $signature = $request->headers->get('X-Esa-Signature');

        if ($signature && !$validator->isValid($payload, $signature)) {
            throw new NotFoundHttpException();
        }

        $body = json_decode($request->getContent(), true);

        switch ($body['kind']) {
            case 'post_create':
            case 'post_update':
                $esa->getPost($body['post']['number'], true);
                $logger->debug(sprintf('Cache for post %d is warmed up!', $body['post']['number']));
                break;
            default:
                break;
        }

        return new JsonResponse('OK');
    }
}
