<?php

namespace Ttskch;

use Doctrine\Common\Cache\Cache;
use Polidog\Esa\Client as EsaClient;

class Esa
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

    /**
     * @param $html
     * @return array
     */
    public function getToc($html)
    {
        $html = preg_replace('/\n/', '', $html);

        preg_match_all('/<h(1|2|3)[^>]*id="([^"]+)"[^>]*>(?:(?!<\/h\1>).)*<\/h\1>/', $html, $matches);
        $ids = $matches[2];
        $hTags = $matches[0];

        $names = array_map(function ($v) {
            preg_match('/<\/a>\s*([^\s]+)<\/h\d>$/', $v, $matches);
            return $matches[1];
        }, $hTags);

        return array_combine($ids, $names);
    }
}
