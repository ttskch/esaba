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
        $this->SUT = new AccessRestrictor();
    }

    /**
     * @dataProvider testIsPublicDataProvider
     */
    public function testIsPublic($category, $tags, $expected)
    {
        $this->SUT
            ->setPublicCategories(['a/b', 'c/d/e'])
            ->setPublicTags(['tag1', 'tag2'])
            ->setPrivateCategories(['a/b/c', 'x/y'])
            ->setPrivateTags(['tag3', 'tag4'])
        ;

        $result = $this->SUT->isPublic($category, $tags);

        $this->assertEquals($expected, $result);
    }

    public function testIsPublicDataProvider()
    {
        return [
            [
                'a/b',
                [],
                true,
            ],
            [
                'a/b/c',
                [],
                false,
            ],
            [
                'c/d',
                [],
                false,
            ],
            [
                'a/b/c/d',
                [],
                false,
            ],
            [
                'unknown',
                ['tag1', 'tag99'],
                true,
            ],
            [
                'unknown',
                ['tag1', 'tag3'],
                false,
            ],
            [
                'a/b',
                ['tag4'],
                false,
            ],
            [
                'a/b/c',
                ['tag2'],
                false,
            ],
            [
                'a/b/c/d',
                ['tag2'],
                false,
            ],
        ];
    }
}
