<?php

declare(strict_types=1);

namespace App\Esa;

use Polidog\Esa\Api;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * @see https://docs.esa.io/posts/102
 */
class Proxy
{
    public const CACHE_KEY_PREFIX = 'esaba.esa.proxy';

    public function __construct(private Api $api, private CacheInterface $cache)
    {
    }

    public function getPost(int $postId, bool $force = false): array
    {
        $cacheKey = sprintf('%s.post.%d', self::CACHE_KEY_PREFIX, $postId);

        if ($force) {
            $this->cache->delete($cacheKey);
        }

        $post = $this->cache->get($cacheKey, function (ItemInterface $item) use ($postId) {
            return $this->api->post($postId);
        });

        return $post;
    }

    public function getEmojis(): array
    {
        $cacheKey = sprintf('%s.emojis', self::CACHE_KEY_PREFIX);

        $emojis = $this->cache->get($cacheKey, function (ItemInterface $item) {
            return $this->api->emojis(['include' => 'all'])['emojis'];
        });

        return $emojis;
    }
}
