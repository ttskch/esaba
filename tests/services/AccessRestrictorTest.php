<?php

namespace Ttskch;

use PHPUnit\Framework\TestCase;

class AccessRestrictorTest extends TestCase
{
    /**
     * @var AccessRestrictor
     */
    private $SUT;

    protected function setUp()
    {
        $this->SUT = new AccessRestrictor(
            ['a/b', 'c/d/e'], // public categories
            ['tag1', 'tag2'], // public tags
            ['a/b/c', 'x/y'], // private categories
            ['tag3', 'tag4']  // private tags
        );
    }

    /**
     * @dataProvider testIsPublicDataProvider
     */
    public function testIsPublic($category, $tags, $expected)
    {
        $result = $this->SUT->isPublic($category, $tags);

        $this->assertEquals($expected, $result);
    }

    public function testIsPublicDataProvider()
    {
        return [
            ['a/b',     [],               true],
            ['a/b/c',   [],               false],
            ['c/d',     [],               false],
            ['a/b/c/d', [],               false],
            ['unknown', ['tag1','tag99'], true],
            ['unknown', ['tag1', 'tag3'], false],
            ['a/b',     ['tag4'],         false],
            ['a/b/c',   ['tag2'],         false],
            ['a/b/c/d', ['tag2'],         false],
        ];
    }

    public function testIsPublicWithEmpty()
    {
        $this->SUT = new AccessRestrictor(
            [],               // public categories
            ['tag1', 'tag2'], // public tags
            ['a/b/c', 'x/y'], // private categories
            ['tag3', 'tag4']  // private tags
        );

        $this->assertTrue($this->SUT->isPublic('a/n/y', []));
        $this->assertTrue($this->SUT->isPublic('a/n/y', ['tag1']));
        $this->assertFalse($this->SUT->isPublic('a/n/y', ['tag3']));
        $this->assertFalse($this->SUT->isPublic('a/n/y', ['tag1', 'tag3']));

        $this->SUT = new AccessRestrictor(
            ['a/b', 'c/d/e'], // public categories
            [],               // public tags
            ['a/b/c', 'x/y'], // private categories
            ['tag3', 'tag4']  // private tags
        );

        $this->assertFalse($this->SUT->isPublic('unknown', ['any1', 'any2']));
        $this->assertFalse($this->SUT->isPublic('unknown', ['any1', 'tag3']));
    }

    public function testIsPublished()
    {
        $this->assertTrue($this->SUT->isPublished('a/b', []));
        $this->assertTrue($this->SUT->isPublished('unknown', ['tag1']));
        $this->assertFalse($this->SUT->isPublished('unknown', ['tag5']));
    }

    public function testIsWithheld()
    {
        $this->assertTrue($this->SUT->isWithheld('a/b/c', []));
        $this->assertTrue($this->SUT->isWithheld('unknown', ['tag3']));
        $this->assertFalse($this->SUT->isWithheld('a/b', []));
    }

    /**
     * @dataProvider categoryIsUnderOneOfDataProvider
     */
    public function testCategoryIsUnderOneOf($needle, $haystacks, $expected)
    {
        $this->assertEquals($expected, $this->SUT->categoryIsUnderOneOf($needle, $haystacks));
    }

    public function categoryIsUnderOneOfDataProvider()
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
    public function testAtLeastOneTagIsIn($needles, $haystacks, $expected)
    {
        $this->assertEquals($expected, $this->SUT->atLeastOneTagIsIn($needles, $haystacks));
    }

    public function atLeastOneTagIsInDataProvider()
    {
        return [
            [['tag1'],         ['tag1','tag2'],         true],
            [['tag1', 'tag2'], ['tag1','tag3', 'tag4'], true],
            [['tag1', 'tag2'], ['tag1','tag2'],         true],
            [['tag1', 'tag2'], ['tag3','tag4'],         false],
            [[],               ['tag1','tag2'],         false],
        ];
    }
}
