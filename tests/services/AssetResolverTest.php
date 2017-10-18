<?php

namespace Ttskch;

use PHPUnit\Framework\TestCase;

class AssetResolverTest extends TestCase
{
    private $SUT;

    /**
     * @dataProvider getAssetPathsDataProvider
     */
    public function testGetAssetPaths($category, $tags, $expected)
    {
        $this->SUT = new AssetResolver([
            '#x'  => ['css' => 'tag-x.css',       'js' => 'tag-x.js'],
            '#y'  => ['css' => 'tag-y.css',       'js' => 'tag-y.js'],
            'a/b' => ['css' => 'category-ab.css', 'js' => 'category-ab.js'],
            'c/d' => ['css' => 'category-cd.css', 'js' => 'category-cd.js'],
        ]);

        $this->assertEquals($expected, $this->SUT->getAssetPaths($category, $tags));
    }

    public function getAssetPathsDataProvider()
    {
        return [
            ['a/b',     ['p', 'q'], ['css' => 'category-ab.css',               'js' => 'category-ab.js']],
            ['a/b/c',   ['p', 'q'], ['css' => 'category-ab.css',               'js' => 'category-ab.js']],
            ['c/d',     ['p', 'q'], ['css' => 'category-cd.css',               'js' => 'category-cd.js']],
            ['c/d',     ['p', 'x'], ['css' => 'tag-x.css',                     'js' => 'tag-x.js']],
            ['unknown', ['p', 'q'], ['css' => AssetResolver::DEFAULT_CSS_PATH, 'js' => AssetResolver::DEFAULT_JS_PATH]],
        ];
    }

    public function testGetAssetPathsWithEmptyConfig()
    {
        $this->SUT = new AssetResolver([
            'a/b' => ['css' => 'category-ab.css'],
            '#x' => ['js' => 'tag-x.js'],
        ]);

        $this->assertEquals([
            'css' => 'category-ab.css',
            'js' => AssetResolver::DEFAULT_JS_PATH,
        ], $this->SUT->getAssetPaths('a/b', []));

        $this->assertEquals([
            'css' => AssetResolver::DEFAULT_CSS_PATH,
            'js' => 'tag-x.js',
        ], $this->SUT->getAssetPaths('unknown', ['x']));

        $this->SUT = new AssetResolver([]);

        $this->assertEquals([
            'css' => AssetResolver::DEFAULT_CSS_PATH,
            'js' => AssetResolver::DEFAULT_JS_PATH,
        ], $this->SUT->getAssetPaths('unknown', []));
    }

    /**
     * @dataProvider getAssetPathsWithOverlappingCategoryBasedConfigDataProvider
     */
    public function testGetAssetPathsWithOverlappingCategoryBasedConfig($category, $expected)
    {
        $this->SUT = new AssetResolver([
            'a/b'   => ['css' => 'category-ab.css',  'js' => 'category-ab.js'],
            'a/b/c' => ['css' => 'category-abc.css', 'js' => 'category-abc.js'],
        ]);

        $this->assertEquals($expected, $this->SUT->getAssetPaths($category, []));

        $this->SUT = new AssetResolver([
            'a/b/c' => ['css' => 'category-abc.css', 'js' => 'category-abc.js'],
            'a/b'   => ['css' => 'category-ab.css',  'js' => 'category-ab.js'],
        ]);

        $this->assertEquals($expected, $this->SUT->getAssetPaths($category, []));
    }

    public function getAssetPathsWithOverlappingCategoryBasedConfigDataProvider()
    {
        return [
            ['a/b',     ['css' => 'category-ab.css',  'js' => 'category-ab.js']],
            ['a/b/c',   ['css' => 'category-abc.css', 'js' => 'category-abc.js']],
            ['a/b/c/d', ['css' => 'category-abc.css', 'js' => 'category-abc.js']],
        ];
    }
}
