<?php

declare(strict_types=1);

namespace App\Tests\Esa;

use App\Esa\EmojiManager;
use App\Esa\Exception\UndefinedEmojiException;
use App\Esa\HtmlHandler;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Generator\UrlGenerator;

class HtmlHandlerTest extends TestCase
{
    use ProphecyTrait;

    private HtmlHandler $SUT;
    private ObjectProphecy $crawler;
    private ObjectProphecy $urlGenerator;
    private ObjectProphecy $emojiManager;

    protected function setUp(): void
    {
        $this->crawler = $this->prophesize(Crawler::class);
        $this->urlGenerator = $this->prophesize(UrlGenerator::class);
        $this->emojiManager = $this->prophesize(EmojiManager::class);

        $this->crawler->count()->willReturn(1);
        $this->urlGenerator->generate(Argument::cetera())->willReturn('esaba');

        $this->SUT = new HtmlHandler($this->crawler->reveal(), $this->urlGenerator->reveal(), $this->emojiManager->reveal(), 'team_name');
    }

    /**
     * @dataProvider uninitializedExceptionDataProvider
     */
    public function testUninitializedException(string $method, ...$args): void
    {
        $this->crawler->count()->willReturn(0);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Initialize before using.');

        $this->SUT->$method(@$args[0], @$args[1]);
    }

    public function uninitializedExceptionDataProvider(): array
    {
        return [
            ['dumpHtml'],
            ['replaceHtml', []],
            ['replaceText', []],
            ['replaceATagWithWalker', '', function () {}],
            ['getToc'],
        ];
    }

    public function testInitialize(): void
    {
        $this->crawler->clear()->shouldBeCalled();
        $this->crawler->addHtmlContent(Argument::type('string'))->willReturn('html');

        $result = $this->SUT->initialize('html');

        $this->assertEquals($this->SUT, $result);
    }

    public function testDumpHtml(): void
    {
        $this->crawler->html()->willReturn('html');

        $result = $this->SUT->dumpHtml();

        $this->assertEquals('html', $result);
    }

    public function testReplaceHtml(): void
    {
        $this->crawler->html()->willReturn('html');
        $this->crawler->clear()->shouldBeCalled();
        $this->crawler->addHtmlContent('replaced html')->shouldBeCalled();

        $this->SUT->replaceHtml([
            '/h/' => 'replaced h',
        ]);
    }

    public function testReplaceText(): void
    {
        $html = '<p class="pattern">pattern</p>';
        $domDocument1 = $this->createDomDocument($html);

        $replacements = [
            '/pattern/' => 'replacement',
        ];

        $domDocument2 = clone $domDocument1;
        $this->SUT->walkDomNodesAndReplaceOnlyTextNodes($domDocument2, $replacements);

        $this->crawler->getNode(0)->willReturn($domDocument1);
        $this->crawler->clear()->shouldBeCalled();
        $this->crawler->addNode($domDocument2)->shouldBeCalled();

        $this->SUT->replaceText($replacements);
    }

    public function testWalkDomNodesAndReplaceOnlyTextNodes(): void
    {
        $html = '<p class="pattern">pattern</p>';
        $domDocument = $this->createDomDocument($html);

        $replacements = [
            '/pattern/' => 'replacement',
        ];

        $this->SUT->walkDomNodesAndReplaceOnlyTextNodes($domDocument, $replacements);

        $this->assertEquals('replacement', $domDocument->textContent);
    }

    /**
     * Integration test for methods of replacePostUrls, disableMentionLinks.
     *
     * @test
     */
    public function integrationTestForReplacingATag(): void
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

    public function testReplaceATagWithWalker(): void
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

    public function testGetPostUrlPattern(): void
    {
        $backReferenceNumberForPostId = null;
        $backReferenceNumberForAnchorHash = null;
        $pattern = $this->SUT->getPostUrlPattern($backReferenceNumberForPostId, $backReferenceNumberForAnchorHash);

        $this->assertTrue(is_string($pattern));
    }

    public function testGetMentionLinkPattern(): void
    {
        $pattern = $this->SUT->getMentionLinkPattern();

        $this->assertTrue(is_string($pattern));
    }

    /**
     * @dataProvider getATagReducerDataProvider
     */
    public function testGetATagReducer(string $subject, bool $expect): void
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

    public function getATagReducerDataProvider(): array
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

    public function testGetATagWalkerForPostUrls(): void
    {
        $walker = $this->SUT->getATagWalkerForPostUrls('/(.)-(.)/', 1, 2, '', '');
        $this->assertInstanceOf(\Closure::class, $walker);

        $this->crawler->attr('href')->willReturn('1-2');
        $replacements = $walker($this->crawler->reveal());

        $this->assertEquals('/href=(\'|")1-2\1/', $replacements['pattern']);
        $this->assertEquals('href="esaba2"', $replacements['replacement']);
    }

    public function testGetATagWalkerForMentionLinks(): void
    {
        $walker = $this->SUT->getATagWalkerForMentionLinks('/(.)/');
        $this->assertInstanceOf(\Closure::class, $walker);

        $this->crawler->attr('href')->willReturn('href');
        $replacements = $walker($this->crawler->reveal());
        $this->assertEquals(count(array_column($replacements, 'pattern')), count(array_column($replacements, 'replacement')));
    }

    /**
     * @dataProvider replaceEmojiCodesDataProvider
     */
    public function testReplaceEmojiCodes(string $code): void
    {
        $html = sprintf('<p class="ignore-me :%s:">replace-me :%s:</p>', $code, $code);
        $tempHtml = sprintf('<p class="ignore-me :%s:">replace-me __ESABA_IMG_TAG__%s__ESABA_IMG_TAG__</p>', $code, $code);
        $imgTag = sprintf('<img src="%s" class="emoji" title=":%s:" alt=":%s:">', 'url', $code, $code);
        $replacedHtml = sprintf('<p class="ignore-me :%s:">replace-me %s</p>', $code, $imgTag);

        // in replaceEmojiCodes()
        $this->crawler->text()->willReturn(sprintf('replace-me :%s:', $code));
        $this->emojiManager->getImageUrl($code)->willReturn('url');

        // in replaceText()
        $this->crawler->getNode(0)->willReturn($dom = $this->createDomDocument($html));
        $this->crawler->clear()->shouldBeCalled();
        $this->crawler->addNode(Argument::type(\DOMNode::class))->shouldBeCalled();

        // in replaceHtml()
        $this->crawler->html()->willReturn($tempHtml);
        $this->crawler->clear()->shouldBeCalled();
        // final replaced html is equal to expected replacedHtml.
        $this->crawler->addHtmlContent($replacedHtml)->shouldBeCalled();

        $this->SUT->replaceEmojiCodes();

        // replaced DomDocument contains tempHtml correctly.
        $this->assertStringContainsString($tempHtml, $dom->saveHTML());
    }

    /**
     * @dataProvider replaceEmojiCodesDataProvider
     */
    public function testReplaceEmojiCodesForDuplicatedEmojis(string $code): void
    {
        $html = sprintf('<p class="ignore-me :%s:">replace-me :%s::%s:</p>', $code, $code, $code);
        $tempHtml = sprintf('<p class="ignore-me :%s:">replace-me __ESABA_IMG_TAG__%s__ESABA_IMG_TAG____ESABA_IMG_TAG__%s__ESABA_IMG_TAG__</p>', $code, $code, $code);
        $imgTag = sprintf('<img src="%s" class="emoji" title=":%s:" alt=":%s:">', 'url', $code, $code);
        $replacedHtml = sprintf('<p class="ignore-me :%s:">replace-me %s%s</p>', $code, $imgTag, $imgTag);

        // in replaceEmojiCodes()
        $this->crawler->text()->willReturn(sprintf('replace-me :%s:', $code));
        $this->emojiManager->getImageUrl($code)->willReturn('url');

        // in replaceText()
        $this->crawler->getNode(0)->willReturn($dom = $this->createDomDocument($html));
        $this->crawler->clear()->shouldBeCalled();
        $this->crawler->addNode(Argument::type(\DOMNode::class))->shouldBeCalled();

        // in replaceHtml()
        $this->crawler->html()->willReturn($tempHtml);
        $this->crawler->clear()->shouldBeCalled();
        // final replaced html is equal to expected replacedHtml.
        $this->crawler->addHtmlContent($replacedHtml)->shouldBeCalled();

        $this->SUT->replaceEmojiCodes();

        // replaced DomDocument contains tempHtml correctly.
        $this->assertStringContainsString($tempHtml, $dom->saveHTML());
    }

    /**
     * @dataProvider replaceEmojiCodesDataProvider
     */
    public function testReplaceEmojiCodesInHeadings(string $code): void
    {
        $htmlTemplate = <<<EOS1
<body>
    <h2 id="0-1-0" name="0-1-0">
        <a class="anchor" id=":%s: h-text" name=":%s:%%20h-text" href="#:%s:%%20h-text">
            <i class="fa fa-link"></i><span class="hidden" data-text="__colon__%s__colon__ h-text"> &gt; __colon__%s__colon__ h-text</span>
        </a>:%s: h-text
    </h2>
</body>
EOS1;

        $tempHtmlTemplate = <<<EOS2
<body>
    <h2 id="0-1-0" name="0-1-0">
        <a class="anchor" id=":%s: h-text" name=":%s:%%20h-text" href="#:%s:%%20h-text">
            <i class="fa fa-link"></i><span class="hidden" data-text="__colon__%s__colon__ h-text"> &gt; __colon__%s__colon__ h-text</span>
        </a>__ESABA_IMG_TAG__%s__ESABA_IMG_TAG__ h-text
    </h2>
</body>
EOS2;

        $replacedHtmlTemplate = <<<EOS3
<body>
    <h2 id="0-1-0" name="0-1-0">
        <a class="anchor" id=":%s: h-text" name=":%s:%%20h-text" href="#:%s:%%20h-text">
            <i class="fa fa-link"></i><span class="hidden" data-text="__colon__%s__colon__ h-text"> &gt; __colon__%s__colon__ h-text</span>
        </a><img src="%s" class="emoji" title=":%s:" alt=":%s:"> h-text
    </h2>
</body>
EOS3;

        $html = preg_replace('/\n\s+/', '', sprintf($htmlTemplate, $code, $code, $code, $code, $code, $code));
        $tempHtml = preg_replace('/\n\s+/', '', sprintf($tempHtmlTemplate, $code, $code, $code, $code, $code, $code));
        $replacedHtml = preg_replace('/\n\s+/', '', sprintf($replacedHtmlTemplate, $code, $code, $code, $code, $code, 'url', $code, $code));

        // in replaceEmojiCodes()
        $this->crawler->text()->willReturn(sprintf('replace-me :%s:', $code));
        $this->emojiManager->getImageUrl($code)->willReturn('url');

        // in replaceText()
        $this->crawler->getNode(0)->willReturn($dom = $this->createDomDocument($html));
        $this->crawler->clear()->shouldBeCalled();
        $this->crawler->addNode(Argument::type(\DOMNode::class))->shouldBeCalled();

        // in replaceHtml()
        $this->crawler->html()->willReturn($tempHtml);
        $this->crawler->clear()->shouldBeCalled();
        // final replaced html is equal to expected replacedHtml.
        $this->crawler->addHtmlContent($replacedHtml)->shouldBeCalled();

        $this->SUT->replaceEmojiCodes();

        // replaced DomDocument contains tempHtml correctly.
        $this->assertStringContainsString($tempHtml, $dom->saveHTML());
    }

    public function replaceEmojiCodesDataProvider(): array
    {
        return [
            ['emoji'],
            ['+1'],
            ['smile_cat'],
            ['custom-emoji_code'],
        ];
    }

    /**
     * @dataProvider replaceEmojiCodesForConfusablePatternDataProvider
     */
    public function testReplaceEmojiCodesForConfusablePattern(string $pattern): void
    {
        $html = sprintf('<p>%s</p>', $pattern);
        $tempHtml = $html;
        $replacedHtml = $html;

        // in replaceEmojiCodes()
        $this->crawler->text()->willReturn(sprintf('%s', $pattern));
        $this->emojiManager->getImageUrl()->shouldNotBeCalled();

        // in replaceText()
        $this->crawler->getNode(0)->willReturn($dom = $this->createDomDocument($html));
        $this->crawler->clear()->shouldBeCalled();
        $this->crawler->addNode(Argument::type(\DOMNode::class))->shouldBeCalled();

        // in replaceHtml()
        $this->crawler->html()->willReturn($tempHtml);
        $this->crawler->clear()->shouldBeCalled();
        // final replaced html is equal to expected replacedHtml.
        $this->crawler->addHtmlContent($replacedHtml)->shouldBeCalled();

        $this->SUT->replaceEmojiCodes();

        // replaced DomDocument contains tempHtml correctly.
        $this->assertStringContainsString($tempHtml, $dom->saveHTML());
    }

    public function replaceEmojiCodesForConfusablePatternDataProvider(): array
    {
        return [
            ['<a href="https://foo/bar">https://foo/bar</a>'],  // ://foo/bar">https:
        ];
    }

    public function testReplaceEmojiCodesWithUndefinedEmojiCode(): void
    {
        $html = '<p>:undefined:</p>';
        $tempHtml = '<p>__ESABA_IMG_TAG__undefined__ESABA_IMG_TAG__</p>';
        $replacedHtml = $html;

        // in replaceEmojiCodes()
        $this->crawler->text()->willReturn(':undefined:');
        $this->emojiManager->getImageUrl('undefined')->willThrow(UndefinedEmojiException::class);

        // in replaceText()
        $this->crawler->getNode(0)->willReturn($dom = $this->createDomDocument($html));
        $this->crawler->clear()->shouldBeCalled();
        $this->crawler->addNode(Argument::type(\DOMNode::class))->shouldBeCalled();

        // in replaceHtml()
        $this->crawler->html()->willReturn($tempHtml);
        $this->crawler->clear()->shouldBeCalled();
        // final replaced html is equal to expected replacedHtml.
        $this->crawler->addHtmlContent($replacedHtml)->shouldBeCalled();

        $this->SUT->replaceEmojiCodes();

        // replaced DomDocument contains tempHtml correctly.
        $this->assertStringContainsString($tempHtml, $dom->saveHTML());
    }

    public function testGetToc(): void
    {
        $this->crawler->filter('h1, h2, h3')->willReturn($this->crawler->reveal());
        $this->crawler->each(Argument::type(\Closure::class))->willReturn(['map']);

        $toc = $this->SUT->getToc();
        $this->assertEquals(['map'], $toc);
    }

    public function testGetWalkerForToc(): void
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

    /**
     * @see Crawler::addHtmlContent
     */
    private function createDomDocument(string $html): \DOMDocument
    {
        $domDocument = new \DOMDocument('1.0', 'UTF-8');
        $domDocument->validateOnParse = true;

        try {
            // Convert charset to HTML-entities to work around bugs in DOMDocument::loadHTML()
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        } catch (\Exception $e) {
        }

        if ('' !== trim($html)) {
            @$domDocument->loadHTML($html);
        }

        return $domDocument;
    }
}
