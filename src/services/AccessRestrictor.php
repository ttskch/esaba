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

        // publish if no public categories are specified.
        if (empty($this->publicCategories)) {
            $isPublic = true;
        }

        // publish if no public tags are specified.
        if (empty($this->publicTags)) {
            $isPublic = true;
        }

        // publish if category is under one of public categories.
        if (!$isPublic) {
            foreach ($this->publicCategories as $publicCategory) {
                if (preg_match(sprintf('#^%s#', $publicCategory), $category)) {
                    $isPublic = true;
                    break;
                }
            }
        }

        // publish if at least one tag is one of public tags.
        if (!$isPublic) {
            foreach ($this->publicTags as $publicTag) {
                foreach ($tags as $tag) {
                    if (preg_match(sprintf('/%s/', $publicTag), $tag)) {
                        $isPublic = true;
                        break 2;
                    }
                }
            }
        }

        // unpublish if category is under one of private categories.
        foreach ($this->privateCategories as $privateCategory) {
            if (preg_match(sprintf('#^%s#', $privateCategory), $category)) {
                $isPublic = false;
                break;
            }
        }

        // unpublish if at least one tag is one of private tags.
        if ($isPublic) {
            foreach ($this->privateTags as $privateTag) {
                foreach ($tags as $tag) {
                    if (preg_match(sprintf('/%s/', $privateTag), $tag)) {
                        $isPublic = false;
                        break 2;
                    }
                }
            }
        }

        return $isPublic;
    }

    /**
     * @param array $publicCategories
     * @return $this
     */
    public function setPublicCategories($publicCategories)
    {
        $this->publicCategories = $publicCategories;

        return $this;
    }

    /**
     * @param array $publicTags
     * @return $this
     */
    public function setPublicTags($publicTags)
    {
        $this->publicTags = $publicTags;

        return $this;
    }

    /**
     * @param array $privateCategories
     * @return $this
     */
    public function setPrivateCategories($privateCategories)
    {
        $this->privateCategories = $privateCategories;

        return $this;
    }

    /**
     * @param array $privateTags
     * @return $this
     */
    public function setPrivateTags($privateTags)
    {
        $this->privateTags = $privateTags;

        return $this;
    }
}
