<?php

declare(strict_types=1);

namespace App\Esa;

use App\Esa\Exception\UndefinedEmojiException;

class EmojiManager
{
    private $emojis;

    public function __construct(private Proxy $esa)
    {
        $this->emojis = $this->flattenEmojis($this->esa->getEmojis());
    }

    public function getImageUrl(string $code): string
    {
        foreach ($this->emojis as $key => $url) {
            if ($key === $code) {
                return $url;
            }
        }

        throw new UndefinedEmojiException();
    }

    private function flattenEmojis(array $emojis): array
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
