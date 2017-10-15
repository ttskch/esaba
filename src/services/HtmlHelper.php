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
     * @param array $replacements
     */
    public function __construct(array $replacements, $teamName, UrlGeneratorInterface $urlGenerator)
    {
        $this->replacements = $replacements;
        $this->teamName = $teamName;
        $this->urlGenerator = $urlGenerator;
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

        $replacements = array_merge($this->replacements, [
            sprintf('#%s/posts/(\d+)(?:/|/edit/?)?["\']#', $esaBaseUrlPattern) => $postUrlReplacement,
            '#[\'"]/members/([^\'"]+)[\'"]#' => sprintf('https://%s.esa.io/members/\1', $this->teamName),
        ]);

        foreach ($replacements as $pattern => $replacement) {
            $html = preg_replace($pattern, $replacement, $html);
        }

        return $html;
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
            preg_match('/<\/a>\s*([^\s]+)<\/h\d>$/i', $v, $matches);
            return $matches[1];
        }, $hTags);

        return array_combine($ids, $names);
    }
}
