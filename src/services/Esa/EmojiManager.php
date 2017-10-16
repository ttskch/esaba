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
        $this->emojis = $this->esa->getEmojis();
    }

    /**
     * @param $code
     * @return string
     */
    public function getImageUrl($code)
    {
        foreach ($this->emojis as $emoji) {
            if ($emoji['code'] === $code) {
                return $emoji['url'];
            }

            foreach ($emoji['aliases'] as $alias) {
                if ($alias === $code) {
                    return $emoji['url'];
                }
            }
        }

        throw new \LogicException('Undefined emoji code.');
    }
}
