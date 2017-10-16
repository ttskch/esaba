<?php

namespace Ttskch;

use Doctrine\Common\Cache\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

class EmojiClient
{
    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $teamName;

    /**
     * @var Client|null
     */
    private $httpClient;

    /**
     * @var Cache
     */
    private $cache;

    const CACHE_KEY_PREFIX = 'ttskch-emoji';

    public function __construct($accessToken, $teamName, Cache $cache, $httpClient = null)
    {
        $this->accessToken = $accessToken;
        $this->teamName = $teamName;
        $this->cache = $cache;

        $httpOptions = [
            'base_uri' => 'https://api.esa.io/v1/',
            'timeout' => 60,
            'allow_redirect' => false,
            'headers' => [
                'User-Agent' => 'esa-php-api v1',
                'Accept'     => 'application/json',
            ]
        ];
        $httpOptions['handler'] = $this->createAuthStack();

        $this->httpClient = !is_null($httpClient) ? $httpClient : new Client($httpOptions);
    }

    /**
     * @return array
     */
    public function getEmojiTable()
    {
        $cacheKey = sprintf('%s.emojis', self::CACHE_KEY_PREFIX);

        if ($table = $this->cache->fetch($cacheKey)) {
            return $table;
        }

        $table = [];

        $response = $this->httpClient->request('GET', sprintf('teams/%s/emojis?include=all', $this->teamName));
        $body = json_decode($response->getBody()->getContents(), true);

        foreach ($body['emojis'] as $emoji) {
            $table[$emoji['code']] = $emoji['url'];
            foreach ($emoji['aliases'] as $alias) {
                $table[$alias] = $emoji['url'];
            }
        }

        $this->cache->save($cacheKey, $table);

        return $table;
    }

    private function createAuthStack()
    {
        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request->withHeader('Authorization', 'Bearer ' . $this->accessToken);
        }));

        return $stack;
    }
}
