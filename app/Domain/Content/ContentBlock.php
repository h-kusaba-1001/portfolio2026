<?php

declare(strict_types=1);

namespace App\Domain\Content;

/**
 * H2 見出しと、その配下の HTML の組。
 *
 * `heading` は表示用（原文）、`key` は照合用（正規化済み）。
 * 役割が違うため属性を分けている。
 */
final readonly class ContentBlock
{
    public string $key;

    public function __construct(
        public string $heading,
        public string $html,
    ) {
        $this->key = HeadingKey::from($heading);
    }
}
