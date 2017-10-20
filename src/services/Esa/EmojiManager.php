<?php

namespace Ttskch\Esa;

class EmojiManager
{
    /**
     * @var Proxy
     */
    private $esa;

    /**
     * @var array
     */
    private $emojis;

    public function __construct(Proxy $esa)
    {
        $this->esa = $esa;
        $this->emojis = $this->flattenEmojis($this->esa->getEmojis());
    }

    /**
     * @param $code
     * @return string
     */
    public function getImageUrl($code)
    {
        foreach ($this->emojis as $key => $url) {
            if ($key === $code) {
                return $url;
            }
        }

        throw new \LogicException('Undefined emoji code.');
    }

    /**
     * @param array $emojis
     * @return array
     */
    public function flattenEmojis(array $emojis)
    {
        $flattened = [];

        foreach ($emojis as $emoji) {
            $flattened[$emoji['code']] = $emoji['url'];

            foreach ($emoji['aliases'] as $alias) {
                $flattened[$alias] = $emoji['url'];
            }
        }

        return $flattened;
    }
}
