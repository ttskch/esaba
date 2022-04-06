<?php

declare(strict_types=1);

namespace App\Esa;

use App\Esa\Exception\UndefinedEmojiException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HtmlHandler
{
    public function __construct(
        private Crawler $crawler,
        private UrlGeneratorInterface $urlGenerator,
        private EmojiManager $emojiManager,
        private string $teamName,
    ) {
    }

    public function initialize(string $html): self
    {
        if (!$html) {
            $html = '<div></div>';
        }

        $this->crawler->clear();
        $this->crawler->addHtmlContent($html);

        return $this;
    }

    public function dumpHtml(): string
    {
        $this->ensureInitialized();

        return $this->crawler->html();
    }

    /**
     * @param array $replacements map of [regexp pattern => replacement]
     */
    public function replaceHtml(array $replacements): self
    {
        $this->ensureInitialized();

        $html = $this->crawler->html();

        foreach ($replacements as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html);
        }

        return $this->initialize($html);
    }

    /**
     * @param array $replacements map of [regexp pattern => replacement]
     */
    public function replaceText(array $replacements): self
    {
        $this->ensureInitialized();

        $domNode = $this->crawler->getNode(0);

        $this->walkDomNodesAndReplaceOnlyTextNodes($domNode, $replacements);

        $this->crawler->clear();
        $this->crawler->addNode($domNode);

        return $this;
    }

    /**
     * @param array $replacements map of [regexp pattern => replacement]
     */
    public function walkDomNodesAndReplaceOnlyTextNodes(\DOMNode $node, array $replacements): self
    {
        if (XML_TEXT_NODE === $node->nodeType) {
            foreach ($replacements as $pattern => $replacement) {
                $node->textContent = preg_replace($pattern, $replacement, $node->textContent);
            }

            return $this;
        }

        if (!$node->hasChildNodes()) {
            return $this;
        }

        foreach ($node->childNodes as $childNode) {
            $this->walkDomNodesAndReplaceOnlyTextNodes($childNode, $replacements);
        }

        return $this;
    }

    /**
     * Replace links to other post with links to see the post on esaba.
     */
    public function replacePostUrls(string $routeName, string $routeVariableName): self
    {
        $backReferenceNumberForPostId = null;
        $backReferenceNumberForAnchorHash = null;
        $pattern = $this->getPostUrlPattern($backReferenceNumberForPostId, $backReferenceNumberForAnchorHash);
        $walker = $this->getATagWalkerForPostUrls($pattern, $backReferenceNumberForPostId, $backReferenceNumberForAnchorHash, $routeName, $routeVariableName);

        return $this->replaceATagWithWalker($pattern, $walker);
    }

    /**
     * Disable @mention links.
     */
    public function disableMentionLinks(): self
    {
        $pattern = $this->getMentionLinkPattern();
        $walker = $this->getATagWalkerForMentionLinks($pattern);

        return $this->replaceATagWithWalker($pattern, $walker);
    }

    /**
     * Replace <a> tag href values for specified regexp pattern with closure returns map of ['pattern' => regexp pattern, 'replacement' => replacement].
     */
    public function replaceATagWithWalker(string $pattern, \Closure $walker): self
    {
        $this->ensureInitialized();

        $targetATags = $this->crawler->filter('a')->reduce($this->getATagReducer($pattern));
        $replacements = $targetATags->each($walker);
        $replacements = array_combine(array_column($replacements, 'pattern'), array_column($replacements, 'replacement'));

        return $this->replaceHtml($replacements);
    }

    /**
     * @param ?int $backReferenceNumberForPostId     for returning position of post id in regexp pattern
     * @param ?int $backReferenceNumberForAnchorHash for returning position of anchor hash regexp pattern
     */
    public function getPostUrlPattern(?int &$backReferenceNumberForPostId, ?int &$backReferenceNumberForAnchorHash): string
    {
        $backReferenceNumberForPostId = 3;
        $backReferenceNumberForAnchorHash = 5;

        return sprintf('#^((https?:)?//%s\.esa\.io)?/posts/(\d+)(/|/edit/?)?(\#.+)?$#', $this->teamName);
    }

    public function getMentionLinkPattern(): string
    {
        return '#/members/([^\'"]+)#';
    }

    /**
     * Return closure reduces ATags Crawler with regexp pattern for href value.
     */
    public function getATagReducer(string $pattern): \Closure
    {
        $reducer = function (Crawler $node) use ($pattern) {
            preg_match($pattern, $node->attr('href'), $matches);

            return boolval($matches);
        };

        return $reducer;
    }

    /**
     * Return closure returns map of ['pattern' => regexp pattern, 'replacement' => replacement] for href value of post urls.
     */
    public function getATagWalkerForPostUrls(
        string $pattern,
        ?int $backReferenceNumberForPostId,
        ?int $backReferenceNumberForAnchorHash,
        string $routeName,
        string $routeVariableName,
    ): \Closure {
        $that = $this;

        $walker = function (Crawler $node) use ($pattern, $backReferenceNumberForPostId, $backReferenceNumberForAnchorHash, $routeName, $routeVariableName, $that) {
            preg_match($pattern, $node->attr('href'), $matches);
            $href = $matches[0];
            $postId = $matches[$backReferenceNumberForPostId];
            $anchorHash = isset($matches[$backReferenceNumberForAnchorHash]) ? $matches[$backReferenceNumberForAnchorHash] : '';

            $pattern = sprintf('/href=(\'|")%s\1/', str_replace('/', '\/', $href));
            $replacement = sprintf('href="%s%s"', $that->urlGenerator->generate($routeName, [$routeVariableName => (int) $postId]), $anchorHash);

            return [
                'pattern' => $pattern,
                'replacement' => $replacement,
            ];
        };

        return $walker;
    }

    /**
     * Return closure returns map of ['pattern' => regexp pattern, 'replacement' => replacement] for href value of mention links.
     */
    public function getATagWalkerForMentionLinks(string $pattern): \Closure
    {
        $walker = function (Crawler $node) use ($pattern) {
            preg_match($pattern, $node->attr('href'), $matches);
            $href = $matches[0];

            $pattern = sprintf('/href=(\'|")%s\1/', str_replace('/', '\/', $href));
            $replacement = '';

            return [
                'pattern' => $pattern,
                'replacement' => $replacement,
            ];
        };

        return $walker;
    }

    /**
     * Replace emoji codes only in text content of each nodes with img tags.
     */
    public function replaceEmojiCodes(): self
    {
        // find emoji codes.
        preg_match_all('/:([^\s:<>\'\/"]+):/', $this->crawler->text(), $matches);

        $tempReplacements = [];
        foreach (array_unique($matches[1]) as $name) {
            $pattern = sprintf('/:%s:/', preg_quote($name));
            $replacement = sprintf('__ESABA_IMG_TAG__%s__ESABA_IMG_TAG__', $name);

            $tempReplacements[$pattern] = $replacement;
        }

        // set temporarily replaced html content.
        $this->replaceText($tempReplacements);

        $replacements = [];
        foreach (array_values($tempReplacements) as $tempReplacement) {
            preg_match('/__ESABA_IMG_TAG__(.+)__ESABA_IMG_TAG__/', $tempReplacement, $matches);
            $name = $matches[1];

            $pattern = sprintf('/%s/', preg_quote($tempReplacement));
            try {
                $replacement = sprintf('<img src="%s" class="emoji" title=":%s:" alt=":%s:">', $this->emojiManager->getImageUrl($name), $name, $name);
            } catch (UndefinedEmojiException $e) {
                $replacement = sprintf(':%s:', $name);
            }

            $replacements[$pattern] = $replacement;
        }

        return $this->replaceHtml($replacements);
    }

    /**
     * Return map of ['id' => id, 'text' => text] of headings as TOC.
     */
    public function getToc(): array
    {
        $this->ensureInitialized();

        $toc = $this->crawler->filter('h1, h2, h3')->each($this->getWalkerForToc());

        return $toc;
    }

    /**
     * Return closure returns map of ['id' => id, 'text' => text] of h tags.
     */
    public function getWalkerForToc(): \Closure
    {
        $walker = function (Crawler $node) {
            return [
                'id' => $node->attr('id'),
                'text' => trim(str_replace($node->filter('a')->text(), '', $node->text())),
            ];
        };

        return $walker;
    }

    private function ensureInitialized(): void
    {
        if (!$this->crawler->count()) {
            throw new \LogicException('Initialize before using.');
        }
    }
}
