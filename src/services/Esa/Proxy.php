<?php

namespace Ttskch\Esa;

use Doctrine\Common\Cache\Cache;
use Polidog\Esa\Api;

/**
 * @see https://docs.esa.io/posts/102
 */
class Proxy
{
    /**
     * @var Api
     */
    private $api;

    /**
     * @var Cache
     */
    private $cache;

    const CACHE_KEY_PREFIX = 'ttskch.esa.proxy';

    /**
     * @param Client $client
     * @param Cache $cache
     */
    public function __construct(Api $api, Cache $cache)
    {
        $this->api = $api;
        $this->cache = $cache;
    }

    /**
     * @param int $postId
     * @param bool $force
     * @return array
     */
    public function getPost($postId, $force = false)
    {
        $cacheKey = sprintf('%s.post.%d', self::CACHE_KEY_PREFIX, $postId);

        if (!$force && $post = $this->cache->fetch($cacheKey)) {
            return $post;
        }

        $post = $this->api->post($postId);
        $this->cache->save($cacheKey, $post);

        return $post;
    }

    /**
     * @return array
     */
    public function getEmojis()
    {
        $cacheKey = sprintf('%s.emojis', self::CACHE_KEY_PREFIX);

        if ($emojis = $this->cache->fetch($cacheKey)) {
            return $emojis;
        }

        $emojis = $this->api->emojis(['include' => 'all'])['emojis'];
        $this->cache->save($cacheKey, $emojis);

        return $emojis;
    }
}
