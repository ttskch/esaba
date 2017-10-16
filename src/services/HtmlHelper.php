<?php

namespace Ttskch;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HtmlHelper
{
    /**
     * @var array
     */
    private $replacements;

    /**
     * @var string
     */
    private $teamName;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var EmojiClient
     */
    private $emojiClient;

    /**
     * @param array $replacements
     */
    public function __construct(array $replacements, $teamName, UrlGeneratorInterface $urlGenerator, EmojiClient $emojiClient)
    {
        $this->replacements = $replacements;
        $this->teamName = $teamName;
        $this->urlGenerator = $urlGenerator;
        $this->emojiClient = $emojiClient;
    }

    /**
     * @param $html
     * @param $postRouteName
     * @param $postRouteIdParamName
     * @return mixed
     */
    public function replace($html, $postRouteName, $postRouteIdParamName)
    {
        // by default, link to other post and link to team member (@mention) will be replaced.

        $esaBaseUrlPattern = sprintf('["\'](?:(?:https?:)?//%s.esa.io)?', $this->teamName);
        $postUrlReplacement = str_replace('0000', '\1', $this->urlGenerator->generate($postRouteName, [$postRouteIdParamName => '0000']));

        // user configured replacements
        $replacements = $this->replacements;

        // default replacements
        $replacements = array_merge($replacements, [
            sprintf('#%s/posts/(\d+)(?:/|/edit/?)?["\']#', $esaBaseUrlPattern) => $postUrlReplacement,
            '#[\'"]/members/([^\'"]+)[\'"]#' => sprintf('https://%s.esa.io/members/\1', $this->teamName),
        ]);

        // emoji replacements
        $replacements = array_merge($replacements, $this->getEmojiReplacements());

        foreach ($replacements as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html);
        }

        return $html;
    }

    /**
     * @return array
     */
    public function getEmojiReplacements()
    {
        $replacements = [];

        $table = $this->emojiClient->getEmojiTable();

        foreach ($table as $code => $url) {
            $replacements[sprintf('/:%s:/', $code)] = sprintf('<img src="%s" title=":%s:" alt=":%s:" class="emoji">', $url, $code, $code);
        }

        return $replacements;
    }

    /**
     * @param $html
     * @return array
     */
    public function getToc($html)
    {
        $html = preg_replace('/\n/', '', $html);

        preg_match_all('/<h(1|2|3)[^>]*id="([^"]+)"[^>]*>(?:(?!<\/h\1>).)*<\/h\1>/i', $html, $matches);
        $ids = $matches[2];
        $hTags = $matches[0];

        $names = array_map(function ($v) {
            preg_match('/<a\s+[^>]*[\'"]anchor[\'"][^>]*>(?:(?!<\/a>).)+<\/a>\s*(.+)<\/h\d>$/i', $v, $matches);
            return filter_var($matches[1], FILTER_SANITIZE_STRING); // strip tags
        }, $hTags);

        return array_combine($ids, $names);
    }
}
