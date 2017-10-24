<?php

namespace Ttskch\Esa;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Generator\UrlGenerator;

class HtmlHandlerTest extends TestCase
{
    /**
     * @var HtmlHandler
     */
    private $SUT;

    /**
     * @var ObjectProphecy
     */
    private $crawler;

    /**
     * @var ObjectProphecy
     */
    private $urlGenerator;

    /**
     * @var ObjectProphecy
     */
    private $emojiManager;

    protected function setUp()
    {
        $this->crawler = $this->prophesize(Crawler::class);
        $this->urlGenerator = $this->prophesize(UrlGenerator::class);
        $this->emojiManager = $this->prophesize(EmojiManager::class);

        $this->crawler->count()->willReturn(1);

        $this->SUT = new HtmlHandler($this->crawler->reveal(), $this->urlGenerator->reveal(), $this->emojiManager->reveal(), 'team_name');
    }

    /**
     * @dataProvider uninitializedExceptionDataProvider
     */
    public function testUninitializedException($method, ...$args)
    {
        $this->crawler->count()->willReturn(0);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Initialize before using.');

        $this->SUT->$method(@$args[0], @$args[1]);
    }

    public function uninitializedExceptionDataProvider()
    {
        return [
            ['dumpHtml'],
            ['replaceATagWithWalker', '', function () {}],
            ['replaceEmojiCodes'],
            ['replaceHtml', []],
            ['getToc'],
        ];
    }

    public function testInitialize()
    {
        $this->crawler->clear()->shouldBeCalled();
        $this->crawler->addHtmlContent(Argument::type('string'))->willReturn('html');

        $result = $this->SUT->initialize('html');

        $this->assertEquals($this->SUT, $result);
    }

    public function testDumpHtml()
    {
        $this->crawler->html()->willReturn('html');

        $result = $this->SUT->dumpHtml();

        $this->assertEquals('html', $result);
    }

    /**
     * Integration test for methods of replacePostUrls, disableMentionLinks.
     *
     * @test
     */
    public function integrationTestForReplacingATag()
    {
        $this->crawler->filter('a')->shouldBeCalledTimes(2)->willReturn($this->crawler->reveal());
        $this->crawler->reduce(Argument::type(\Closure::class))->shouldBeCalledTimes(2)->willReturn($this->crawler->reveal());
        $this->crawler->each(Argument::type(\Closure::class))->shouldBeCalledTimes(2)->willReturn([
            ['pattern' => '/p1/', 'replacement' => 'r1'],
            ['pattern' => '/p2/', 'replacement' => 'r2'],
            ['pattern' => '/p3/', 'replacement' => 'r3'],
        ]);

        $this->crawler->html()->shouldBeCalledTimes(2)->willReturn('p1 p2 p3');
        $this->crawler->clear()->shouldBeCalledTimes(2);
        $this->crawler->addHtmlContent('r1 r2 r3')->shouldBeCalledTimes(2);

        $this->SUT->replacePostUrls('', '');
        $this->SUT->disableMentionLinks();
    }

    public function testReplaceATagWithWalker()
    {
        $pattern = '/p/';
        $replacement = 'r';
        $walker = function (Crawler $node) use ($pattern, $replacement) {
            return [
                'pattern' => $pattern,
                'replacement' => $replacement,
            ];
        };

        $this->crawler->filter('a')->willReturn($this->crawler->reveal());
        $this->crawler->reduce(Argument::type(\Closure::class))->willReturn($this->crawler->reveal());
        $this->crawler->each($walker)->willReturn([
            ['pattern' => $pattern, 'replacement' => $replacement],
            ['pattern' => $pattern, 'replacement' => $replacement],
            ['pattern' => $pattern, 'replacement' => $replacement],
        ]);

        $this->crawler->html()->willReturn('p1 p2 p3');
        $this->crawler->clear()->shouldBeCalled();
        $this->crawler->addHtmlContent('r1 r2 r3')->shouldBeCalled();

        $this->SUT->replaceATagWithWalker($pattern, $walker);
    }

    public function testGetPostUrlPattern()
    {
        $backReferenceNumberForPostId = null;
        $backReferenceNumberForAnchorHash = null;
        $pattern = $this->SUT->getPostUrlPattern($backReferenceNumberForPostId, $backReferenceNumberForAnchorHash);

        $this->assertTrue(is_string($pattern));
    }

    public function testGetMentionLinkPattern()
    {
        $pattern = $this->SUT->getMentionLinkPattern();

        $this->assertTrue(is_string($pattern));
    }

    /**
     * @dataProvider getATagReducerDataProvider
     */
    public function testGetATagReducer($subject, $expect)
    {
        $node = $this->prophesize(Crawler::class);
        $node->attr('href')->willReturn($subject);

        $backReferenceNumberForPostId = null;
        $backReferenceNumberForAnchorHash = null;
        $pattern = $this->SUT->getPostUrlPattern($backReferenceNumberForPostId, $backReferenceNumberForAnchorHash);

        $reducer = $this->SUT->getATagReducer($pattern);
        $result = $reducer($node->reveal());

        $this->assertEquals($expect, $result);
    }

    public function getATagReducerDataProvider()
    {
        return [
            ['https://team_name.esa.io/posts/123/edit/', true],
            ['https://team_name.esa.io/posts/123/edit', true],
            ['https://team_name.esa.io/posts/123/', true],
            ['https://team_name.esa.io/posts/123', true],
            ['https://team_name.esa.io/posts/123#1-0-0', true],
            ['http://team_name.esa.io/posts/123/edit/', true],
            ['http://team_name.esa.io/posts/123/edit', true],
            ['http://team_name.esa.io/posts/123/', true],
            ['http://team_name.esa.io/posts/123', true],
            ['http://team_name.esa.io/posts/123#1-0-0', true],
            ['//team_name.esa.io/posts/123/edit/', true],
            ['//team_name.esa.io/posts/123/edit', true],
            ['//team_name.esa.io/posts/123/', true],
            ['//team_name.esa.io/posts/123', true],
            ['//team_name.esa.io/posts/123#1-0-0', true],
            ['/posts/123/edit/', true],
            ['/posts/123/edit/#1-0-0', true],
            ['/posts/123/edit', true],
            ['/posts/123/edit#1-0-0', true],
            ['/posts/123/', true],
            ['/posts/123/#1-0-0', true],
            ['/posts/123', true],
            ['/posts/123/#1-0-0', true],
            ['https://other_team_name.esa.io/posts/123', false],
            ['posts/123', false],
        ];
    }

    public function testGetATagWalkerForPostUrls()
    {
        $walker = $this->SUT->getATagWalkerForPostUrls('/(.)-(.)/', 1, 2,'', '');
        $this->assertInstanceOf(\Closure::class, $walker);

        $this->crawler->attr('href')->willReturn('1-2');
        $replacements = $walker($this->crawler->reveal());

        $this->assertEquals('/href=(\'|")1-2\1/', $replacements['pattern']);
        $this->assertEquals('href="2"', $replacements['replacement']);
    }

    public function testGetATagWalkerForMentionLinks()
    {
        $walker = $this->SUT->getATagWalkerForMentionLinks('/(.)/');
        $this->assertInstanceOf(\Closure::class, $walker);

        $this->crawler->attr('href')->willReturn('href');
        $replacements = $walker($this->crawler->reveal());
        $this->assertEquals(count(array_column($replacements, 'pattern')), count(array_column($replacements, 'replacement')));
    }

    public function testReplaceEmojiCodes()
    {
        $code = 'emoji';
        $imgTag = sprintf('<img src="%s" class="emoji" title=":%s:" alt=":%s:">', 'url', $code, $code);

        $this->crawler->html()->willReturn(sprintf('<p>:%s:</p>', $code));
        $this->crawler->clear()->shouldBeCalled();
        $this->crawler->addHtmlContent(sprintf('<p>%s</p>', $imgTag))->shouldBeCalled();

        $this->emojiManager->getImageUrl($code)->willReturn('url');

        $this->SUT->replaceEmojiCodes();
    }

    public function testReplaceHtml()
    {
        $this->crawler->html()->willReturn('html');
        $this->crawler->clear()->shouldBeCalled();
        $this->crawler->addHtmlContent('replaced html')->shouldBeCalled();

        $this->SUT->replaceHtml([
            '/h/' => 'replaced h',
        ]);
    }

    public function testGetToc()
    {
        $this->crawler->filter('h1, h2, h3')->willReturn($this->crawler->reveal());
        $this->crawler->each(Argument::type(\Closure::class))->willReturn(['map']);

        $toc = $this->SUT->getToc();
        $this->assertEquals(['map'], $toc);
    }

    public function testGetWalkerForToc()
    {
        // extract ['id' => 'id', 'text' => 'h-text'] from <h1 id="id"><a>a-text</a>h-text</h1>

        $walker = $this->SUT->getWalkerForToc();
        $this->assertInstanceOf(\Closure::class, $walker);

        $filteredCrawler = $this->prophesize(Crawler::class);
        $filteredCrawler->text()->willReturn('a-text');

        $this->crawler->attr('id')->willReturn('id');
        $this->crawler->filter('a')->willReturn($filteredCrawler->reveal());
        $this->crawler->text()->willReturn('a-text h-text');

        $replacements = $walker($this->crawler->reveal());
        $this->assertEquals([
            'id' => 'id',
            'text' => 'h-text',
        ], $replacements);
    }
}
