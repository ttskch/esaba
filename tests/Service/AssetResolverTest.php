<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\AssetResolver;
use PHPUnit\Framework\TestCase;

class AssetResolverTest extends TestCase
{
    private AssetResolver $SUT;

    /**
     * @dataProvider getAssetPathsDataProvider
     */
    public function testGetAssetPaths(string $category, array $tags, array $expected): void
    {
        $this->SUT = new AssetResolver([
            '#x' => ['css' => 'tag-x.css',       'js' => 'tag-x.js'],
            '#y' => ['css' => 'tag-y.css',       'js' => 'tag-y.js'],
            'a/b' => ['css' => 'category-ab.css', 'js' => 'category-ab.js'],
            'c/d' => ['css' => 'category-cd.css', 'js' => 'category-cd.js'],
        ]);

        $this->assertEquals($expected, $this->SUT->getAssetPaths($category, $tags));
    }

    public function getAssetPathsDataProvider(): array
    {
        return [
            ['a/b',     ['p', 'q'], ['css' => 'category-ab.css',               'js' => 'category-ab.js']],
            ['a/b/c',   ['p', 'q'], ['css' => 'category-ab.css',               'js' => 'category-ab.js']],
            ['c/d',     ['p', 'q'], ['css' => 'category-cd.css',               'js' => 'category-cd.js']],
            ['c/d',     ['p', 'x'], ['css' => 'tag-x.css',                     'js' => 'tag-x.js']],
            ['unknown', ['p', 'q'], ['css' => AssetResolver::DEFAULT_CSS_PATH, 'js' => AssetResolver::DEFAULT_JS_PATH]],
        ];
    }

    public function testGetAssetPathsWithEmptyConfig(): void
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
    public function testGetAssetPathsWithOverlappingCategoryBasedConfig($category, $expected): void
    {
        $this->SUT = new AssetResolver([
            'a/b' => ['css' => 'category-ab.css',  'js' => 'category-ab.js'],
            'a/b/c' => ['css' => 'category-abc.css', 'js' => 'category-abc.js'],
        ]);

        $this->assertEquals($expected, $this->SUT->getAssetPaths($category, []));

        $this->SUT = new AssetResolver([
            'a/b/c' => ['css' => 'category-abc.css', 'js' => 'category-abc.js'],
            'a/b' => ['css' => 'category-ab.css',  'js' => 'category-ab.js'],
        ]);

        $this->assertEquals($expected, $this->SUT->getAssetPaths($category, []));
    }

    public function getAssetPathsWithOverlappingCategoryBasedConfigDataProvider(): array
    {
        return [
            ['a/b',     ['css' => 'category-ab.css',  'js' => 'category-ab.js']],
            ['a/b/c',   ['css' => 'category-abc.css', 'js' => 'category-abc.js']],
            ['a/b/c/d', ['css' => 'category-abc.css', 'js' => 'category-abc.js']],
        ];
    }

    public function testGetCategoryBasedAssetPaths(): void
    {
        $this->SUT = new AssetResolver([
            'a/b' => ['paths1'],
            'a/b/c' => ['paths2'],
        ]);

        $reflection = new \ReflectionClass($this->SUT);
        $method = $reflection->getMethod('getCategoryBasedAssetPaths');
        $method->setAccessible(true);

        $this->assertEquals(['paths1'], $method->invoke($this->SUT, 'a/b'));
        $this->assertEquals(['paths2'], $method->invoke($this->SUT, 'a/b/c'));
        $this->assertEquals(['paths1'], $method->invoke($this->SUT, 'a/b/d'));
        $this->assertEquals([], $method->invoke($this->SUT, 'unknown'));
    }

    public function testGetTagBasedAssetPaths(): void
    {
        $this->SUT = new AssetResolver([
            '#tag1' => ['paths1'],
            '#tag2' => ['paths2'],
            '#tag3' => ['paths3'],
        ]);

        $reflection = new \ReflectionClass($this->SUT);
        $method = $reflection->getMethod('getTagBasedAssetPaths');
        $method->setAccessible(true);

        $this->assertEquals(['paths1'], $method->invoke($this->SUT, ['tag1']));
        $this->assertEquals(['paths2'], $method->invoke($this->SUT, ['tag2', 'tag4']));
        $this->assertEquals([], $method->invoke($this->SUT, ['tag4', 'tag5']));
        $this->assertContains($method->invoke($this->SUT, ['tag2', 'tag3']), [['paths2'], ['paths3']]); // any one of matched
    }
}
