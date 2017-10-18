<?php

namespace Ttskch;

class AccessRestrictor
{
    /**
     * @var array
     */
    private $publicCategories;

    /**
     * @var array
     */
    private $publicTags;

    /**
     * @var array
     */
    private $privateCategories;

    /**
     * @var array
     */
    private $privateTags;

    public function __construct(array $publicCategories = [], array $publicTags = [], array $privateCategories = [], array $privateTags = [])
    {
        $this->publicCategories = $publicCategories;
        $this->publicTags = $publicTags;
        $this->privateCategories = $privateCategories;
        $this->privateTags = $privateTags;
    }

    /**
     * @param string $category
     * @param array $tags
     * @return bool
     */
    public function isPublic($category, array $tags)
    {
        $isPublic = false;

        if (
            empty($this->publicCategories) ||
            $this->categoryIsUnderOneOf($category, $this->publicCategories) ||
            empty($this->publicTags) ||
            $this->atLeastOnetagIsIn($tags, $this->publicTags)
        ) {
            $isPublic = true;
        }

        if ($this->categoryIsUnderOneOf($category, $this->privateCategories) || $this->atLeastOnetagIsIn($tags, $this->privateTags)) {
            $isPublic = false;
        }

        return $isPublic;
    }

    /**
     * @param string $needle
     * @param array $haystacks
     * @return bool
     */
    public function categoryIsUnderOneOf($needle, array $haystacks)
    {
        foreach ($haystacks as $haystack) {
            if (preg_match(sprintf('#^%s#', $haystack), $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $needles
     * @param array $haystacks
     * @return bool
     */
    public function atLeastOneTagIsIn(array $needles, array $haystacks)
    {
        foreach ($haystacks as $haystack) {
            foreach ($needles as $needle) {
                if ($needle === $haystack) {
                    return true;
                }
            }
        }

        return false;
    }
}
