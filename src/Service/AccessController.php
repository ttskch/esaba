<?php

declare(strict_types=1);

namespace App\Service;

class AccessController
{
    public function __construct(
        private array $publicCategories = [],
        private array $publicTags = [],
        private array $privateCategories = [],
        private array $privateTags = [],
    ) {
    }

    public function isPublic(string $category, array $tags): bool
    {
        if ($this->matchesPrivateConditions($category, $tags)) {
            return false;
        }

        return empty($this->publicCategories) || $this->matchesPublicConditions($category, $tags);
    }

    private function matchesPublicConditions(string $category, array $tags): bool
    {
        return $this->categoryIsUnderOneOf($category, $this->publicCategories) || $this->atLeastOneTagIsIn($tags, $this->publicTags);
    }

    private function matchesPrivateConditions(string $category, array $tags): bool
    {
        return $this->categoryIsUnderOneOf($category, $this->privateCategories) || $this->atLeastOneTagIsIn($tags, $this->privateTags);
    }

    private function categoryIsUnderOneOf(string $needle, array $haystacks): bool
    {
        foreach ($haystacks as $haystack) {
            if (preg_match(sprintf('#^%s#', $haystack), $needle)) {
                return true;
            }
        }

        return false;
    }

    private function atLeastOneTagIsIn(array $needles, array $haystacks): bool
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
