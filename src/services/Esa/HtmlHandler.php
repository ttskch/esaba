<?php

namespace Ttskch\Esa;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HtmlHandler
{
    /**
     * @var Crawler
     */
    private $crawler;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var EmojiManager
     */
    private $emojiManager;

    /**
     * @var string
     */
    private $teamName;

    /**
     * @param array $replacements
     */
    public function __construct(Crawler $crawler, UrlGeneratorInterface $urlGenerator, EmojiManager $emojiManager, $teamName)
    {
        $this->crawler = $crawler;
        $this->urlGenerator = $urlGenerator;
        $this->emojiManager = $emojiManager;
        $this->teamName = $teamName;
    }

    /**
     * @param string $html
     * @return $this
     */
    public function initialize($html)
    {
        $this->crawler->clear();
        $this->crawler->addHtmlContent($html);

        return $this;
    }

    /**
     * @return string
     */
    public function dumpHtml()
    {
        $this->ensureInitialized();

        return $this->crawler->html();
    }

    /**
     * @param array $replacements map of [regexp pattern => replacement].
     */
    public function replaceHtml(array $replacements)
    {
        $this->ensureInitialized();

        $html = $this->crawler->html();

        foreach ($replacements as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html);
        }

        $this->initialize($html);
    }

    /**
     * @param array $replacements map of [regexp pattern => replacement].
     */
    public function replaceText(array $replacements)
    {
        $this->ensureInitialized();

        $domNode = $this->crawler->getNode(0);

        $this->walkDomNodesAndReplaceOnlyTextNodes($domNode, $replacements);

        $this->crawler->clear();
        $this->crawler->addNode($domNode);
    }

    /**
     * @param \DOMNode $node
     * @param array $replacements map of [regexp pattern => replacement].
     */
    public function walkDomNodesAndReplaceOnlyTextNodes(\DOMNode $node, array $replacements)
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            foreach ($replacements as $pattern => $replacement) {
                $node->textContent = preg_replace($pattern, $replacement, $node->textContent);
            }

            return;
        }

        if (!$node->hasChildNodes()) {
            return;
        }

        foreach ($node->childNodes as $childNode) {
            $this->walkDomNodesAndReplaceOnlyTextNodes($childNode, $replacements);
        }
    }

    /**
     * Replace links to other post with links to see the post on esaba.
     *
     * @param string $routeName
     * @param string $routeVariableName
     */
    public function replacePostUrls($routeName, $routeVariableName)
    {
        $backReferenceNumberForPostId = null;
        $backReferenceNumberForAnchorHash = null;
        $pattern = $this->getPostUrlPattern($backReferenceNumberForPostId, $backReferenceNumberForAnchorHash);
        $walker = $this->getATagWalkerForPostUrls($pattern, $backReferenceNumberForPostId, $backReferenceNumberForAnchorHash, $routeName, $routeVariableName);

        $this->replaceATagWithWalker($pattern, $walker);
    }

    /**
     * Disable @mention links.
     */
    public function disableMentionLinks()
    {
        $pattern = $this->getMentionLinkPattern();
        $walker = $this->getATagWalkerForMentionLinks($pattern);

        $this->replaceATagWithWalker($pattern, $walker);
    }

    /**
     * Replace <a> tag href values for specified regexp pattern with closure returns map of ['pattern' => regexp pattern, 'replacement' => replacement].
     *
     * @param string $pattern
     * @param \Closure $walker
     */
    public function replaceATagWithWalker($pattern, \Closure $walker)
    {
        $this->ensureInitialized();

        $targetATags = $this->crawler->filter('a')->reduce($this->getATagReducer($pattern));
        $replacements = $targetATags->each($walker);
        $replacements = array_combine(array_column($replacements, 'pattern'), array_column($replacements, 'replacement'));

        $this->replaceHtml($replacements);
    }

    /**
     * @param string $backReferenceNumberForPostId For returning position of post id in regexp pattern.
     * @param string $backReferenceNumberForAnchorHash For returning position of anchor hash regexp pattern.
     * @return string
     */
    public function getPostUrlPattern(&$backReferenceNumberForPostId, &$backReferenceNumberForAnchorHash)
    {
        $backReferenceNumberForPostId = 3;
        $backReferenceNumberForAnchorHash = 5;

        return sprintf('#^((https?:)?//%s\.esa\.io)?/posts/(\d+)(/|/edit/?)?(\#.+)?$#', $this->teamName);
    }

    /**
     * @return string
     */
    public function getMentionLinkPattern()
    {
        return '#/members/([^\'"]+)#';
    }

    /**
     * Return closure reduces ATags Crawler with regexp pattern for href value.
     *
     * @param string $pattern
     * @return \Closure
     */
    public function getATagReducer($pattern)
    {
        $reducer = function (Crawler $node) use ($pattern) {
            preg_match($pattern, $node->attr('href'), $matches);

            return boolval($matches);
        };

        return $reducer;
    }

    /**
     * Return closure returns map of ['pattern' => regexp pattern, 'replacement' => replacement] for href value of post urls.
     *
     * @param string $pattern
     * @param int $backReferenceNumberForPostId
     * @param int $backReferenceNumberForAnchorHash
     * @param string $routeName
     * @param string $routeVariableName
     * @return \Closure
     */
    public function getATagWalkerForPostUrls($pattern, $backReferenceNumberForPostId, $backReferenceNumberForAnchorHash, $routeName, $routeVariableName)
    {
        $that = $this;

        $walker = function (Crawler $node) use ($pattern, $backReferenceNumberForPostId, $backReferenceNumberForAnchorHash, $routeName, $routeVariableName, $that) {
            preg_match($pattern, $node->attr('href'), $matches);
            $href = $matches[0];
            $postId = $matches[$backReferenceNumberForPostId];
            $anchorHash = isset($matches[$backReferenceNumberForAnchorHash]) ? $matches[$backReferenceNumberForAnchorHash] : '';

            $pattern = sprintf('/href=(\'|")%s\1/', str_replace('/', '\/', $href));
            $replacement = sprintf('href="%s%s"', $that->urlGenerator->generate($routeName, [$routeVariableName => $postId]), $anchorHash);

            return [
                'pattern' => $pattern,
                'replacement' => $replacement,
            ];
        };

        return $walker;
    }

    /**
     * Return closure returns map of ['pattern' => regexp pattern, 'replacement' => replacement] for href value of mention links.
     *
     * @param string $pattern
     * @return \Closure
     */
    public function getATagWalkerForMentionLinks($pattern)
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
    public function replaceEmojiCodes()
    {
        // find emoji codes.
        preg_match_all('/:([^\s:<>\'"]+):/', $this->crawler->text(), $matches);

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
            $replacement = sprintf('<img src="%s" class="emoji" title=":%s:" alt=":%s:">', $this->emojiManager->getImageUrl($name), $name, $name);

            $replacements[$pattern] = $replacement;
        }

        $this->replaceHtml($replacements);
    }

    /**
     * Return map of ['id' => id, 'text' => text] of headings as TOC.
     *
     * @return array
     */
    public function getToc()
    {
        $this->ensureInitialized();

        $toc = $this->crawler->filter('h1, h2, h3')->each($this->getWalkerForToc());

        return $toc;
    }

    /**
     * Return closure returns map of ['id' => id, 'text' => text] of h tags.
     *
     * @return \Closure
     */
    public function getWalkerForToc()
    {
        $walker = function (Crawler $node) {
            return [
                'id' => $node->attr('id'),
                'text' => trim(str_replace($node->filter('a')->text(), '', $node->text())),
            ];
        };

        return $walker;
    }

    private function ensureInitialized()
    {
        if (!$this->crawler->count()) {
            throw new \LogicException('Initialize before using.');
        }
    }
}
