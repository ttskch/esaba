<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\AccessController;
use PHPUnit\Framework\TestCase;

class AccessControllerTest extends TestCase
{
    private AccessController $SUT;

    protected function setUp(): void
    {
        $this->SUT = new AccessController(
            ['a/b', 'c/d/e'], // public categories
            ['tag1', 'tag2'], // public tags
            ['a/b/c', 'x/y'], // private categories
            ['tag3', 'tag4']  // private tags
        );
    }

    /**
     * @dataProvider testIsPublicDataProvider
     */
    public function testIsPublic(string $category, array $tags, bool $expected): void
    {
        $result = $this->SUT->isPublic($category, $tags);

        $this->assertEquals($expected, $result);
    }

    public function testIsPublicDataProvider(): array
    {
        return [
            ['a/b',     [],               true],
            ['a/b/c',   [],               false],
            ['c/d',     [],               false],
            ['a/b/c/d', [],               false],
            ['unknown', ['tag1', 'tag99'], true],
            ['unknown', ['tag1', 'tag3'], false],
            ['a/b',     ['tag4'],         false],
            ['a/b/c',   ['tag2'],         false],
            ['a/b/c/d', ['tag2'],         false],
        ];
    }

    public function testIsPublicWithEmpty(): void
    {
        $this->SUT = new AccessController(
            [],               // public categories
            ['tag1', 'tag2'], // public tags
            ['a/b/c', 'x/y'], // private categories
            ['tag3', 'tag4']  // private tags
        );

        $this->assertTrue($this->SUT->isPublic('a/n/y', []));
        $this->assertTrue($this->SUT->isPublic('a/n/y', ['tag1']));
        $this->assertFalse($this->SUT->isPublic('a/n/y', ['tag3']));
        $this->assertFalse($this->SUT->isPublic('a/n/y', ['tag1', 'tag3']));

        $this->SUT = new AccessController(
            ['a/b', 'c/d/e'], // public categories
            [],               // public tags
            ['a/b/c', 'x/y'], // private categories
            ['tag3', 'tag4']  // private tags
        );

        $this->assertFalse($this->SUT->isPublic('unknown', ['any1', 'any2']));
        $this->assertFalse($this->SUT->isPublic('unknown', ['any1', 'tag3']));
    }

    public function testMatchesPublicConditions(): void
    {
        $reflection = new \ReflectionClass($this->SUT);
        $method = $reflection->getMethod('matchesPublicConditions');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->SUT, 'a/b', []));
        $this->assertTrue($method->invoke($this->SUT, 'unknown', ['tag1']));
        $this->assertFalse($method->invoke($this->SUT, 'unknown', ['tag5']));
    }

    public function testMatchesPrivateConditions(): void
    {
        $reflection = new \ReflectionClass($this->SUT);
        $method = $reflection->getMethod('matchesPrivateConditions');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->SUT, 'a/b/c', []));
        $this->assertTrue($method->invoke($this->SUT, 'unknown', ['tag3']));
        $this->assertFalse($method->invoke($this->SUT, 'a/b', []));
    }

    /**
     * @dataProvider categoryIsUnderOneOfDataProvider
     */
    public function testCategoryIsUnderOneOf(string $needle, array $haystacks, bool $expected): void
    {
        $reflection = new \ReflectionClass($this->SUT);
        $method = $reflection->getMethod('categoryIsUnderOneOf');
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invoke($this->SUT, $needle, $haystacks));
    }

    public function categoryIsUnderOneOfDataProvider(): array
    {
        return [
            ['a/b',       ['a/b', 'c/d/e'], true],
            ['a/b/c',     ['a/b', 'c/d/e'], true],
            ['c/d/e',     ['a/b', 'c/d/e'], true],
            ['c/d/e/f/g', ['a/b', 'c/d/e'], true],
            ['root/a/b',  ['a/b', 'c/d/e'], false],
            ['a/c',       ['a/b', 'c/d/e'], false],
            ['c/d',       ['a/b', 'c/d/e'], false],
        ];
    }

    /**
     * @dataProvider atLeastOneTagIsInDataProvider
     */
    public function testAtLeastOneTagIsIn(array $needles, array $haystacks, bool $expected): void
    {
        $reflection = new \ReflectionClass($this->SUT);
        $method = $reflection->getMethod('atLeastOneTagIsIn');
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invoke($this->SUT, $needles, $haystacks));
    }

    public function atLeastOneTagIsInDataProvider()
    {
        return [
            [['tag1'],         ['tag1', 'tag2'],         true],
            [['tag1', 'tag2'], ['tag1', 'tag3', 'tag4'], true],
            [['tag1', 'tag2'], ['tag1', 'tag2'],         true],
            [['tag1', 'tag2'], ['tag3', 'tag4'],         false],
            [[],               ['tag1', 'tag2'],         false],
        ];
    }
}
