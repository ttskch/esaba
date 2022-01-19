<?php

declare(strict_types=1);

namespace App\Service;

class AssetResolver
{
    public const DEFAULT_CSS_PATH = 'css/post/default.css';
    public const DEFAULT_JS_PATH = 'js/post/default.js';

    private array $categoryBasedConfig;
    private array $tagBasedConfig;

    public function __construct(array $config)
    {
        $this->categoryBasedConfig = [];
        $this->tagBasedConfig = [];

        foreach ($config as $key => $value) {
            if (0 === strpos($key, '#')) {
                $this->tagBasedConfig[$key] = $value;
            } else {
                $this->categoryBasedConfig[$key] = $value;
            }
        }

        // deeper category should win.
        krsort($this->categoryBasedConfig, SORT_NATURAL);
    }

    public function getAssetPaths(string $category, array $tags): array
    {
        $assetPaths = [
            'css' => self::DEFAULT_CSS_PATH,
            'js' => self::DEFAULT_JS_PATH,
        ];

        $categoryBasedAssetPaths = $this->getCategoryBasedAssetPaths($category);
        $tagBasedAssetPaths = $this->getTagBasedAssetPaths($tags);

        return array_merge($assetPaths, $categoryBasedAssetPaths, $tagBasedAssetPaths);
    }

    public function getCategoryBasedAssetPaths(string $category): array
    {
        foreach ($this->categoryBasedConfig as $matcher => $paths) {
            if (preg_match(sprintf('#^%s#', $matcher), $category)) {
                return $paths;  // deeper category should match early.
            }
        }

        return [];
    }

    public function getTagBasedAssetPaths(array $tags): array
    {
        foreach ($this->tagBasedConfig as $matcher => $paths) {
            if (in_array(substr($matcher, 1), $tags)) {
                return $paths;
            }
        }

        return [];
    }
}
