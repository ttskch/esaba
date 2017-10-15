<?php

namespace Ttskch;

class CategoryChecker
{
    /**
     * @var array
     */
    private $whiteList;

    /**
     * @var array
     */
    private $blackList;

    /**
     * @param array $whiteList
     * @param array $blackList
     */
    public function __construct(array $whiteList, array $blackList)
    {
        $this->whiteList = $whiteList;
        $this->blackList = $blackList;
    }

    /**
     * @param $category
     * @return bool
     */
    public function check($category)
    {
        $isWhite = false;

        if (empty($this->whiteList)) {
            $isWhite = true;
        } else {
            foreach ($this->whiteList as $whiteCategory) {
                if (preg_match(sprintf('#^%s#', $whiteCategory), $category)) {
                    $isWhite = true;
                    break;
                }
            }
        }

        foreach ($this->blackList as $blackCategory) {
            if (preg_match(sprintf('#^%s#', $blackCategory), $category)) {
                $isWhite = false;
                break;
            }
        }

        return $isWhite;
    }
}
