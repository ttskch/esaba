<?php

namespace Ttskch;

use Doctrine\Common\Cache\Cache;
use Polidog\Esa\Client as EsaClient;

class EsaProxy
{
    /**
     * @var EsaClient
     */
    private $client;

    /**
     * @var Cache
     */
    private $cache;

    const CACHE_KEY_PREFIX = 'ttskch-esa';

    /**
     * @param EsaClient $client
     * @param Cache $cache
     */
    public function __construct(EsaClient $client, Cache $cache)
    {
        $this->client = $client;
        $this->cache = $cache;
    }

    /**
     * @param $postId
     * @param bool $force
     * @return mixed
     */
    public function getPost($postId, $force = false)
    {
        $cacheKey = sprintf('%s.post.%d', self::CACHE_KEY_PREFIX, $postId);

        if (!$force && $post = $this->cache->fetch($cacheKey)) {
            return $post;
        }

        $post = $this->client->post($postId);
        $this->cache->save($cacheKey, $post);

        return $post;
    }
}
