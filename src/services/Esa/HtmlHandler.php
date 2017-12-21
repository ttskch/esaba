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
     * Replace emoji codes with img tags.
     */
    public function replaceEmojiCodes()
    {
        $this->ensureInitialized();

        $html = $this->crawler->html();

        preg_match_all('/:(\w+):/', $html, $matches);
        $names = $matches[1];

        # remove duplicated occurrence
        $names = array_values(array_unique($names));

        for ($i = 0; $i < count($names); $i++) {
            $name = $names[$i];
            $code = ":${name}:";
            $replacement = sprintf('<img src="%s" class="emoji" title="%s" alt="%s">', $this->emojiManager->getImageUrl($name), $code, $code);

            $html = str_replace($code, $replacement, $html);
        }

        $this->initialize($html);
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
