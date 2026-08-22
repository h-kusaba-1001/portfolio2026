<?php

declare(strict_types=1);

namespace App\Domain\Content;

/**
 * 1 セクションの内容。読み込み成功と失敗の両方を表現する。
 *
 * 「失敗」と「中身が無い」は別物（domain-entities.md §3）:
 *   - H2 を持たないファイル（experience.md / next.md）は成功。lead に本文が入る
 *   - 読み込みに失敗した場合のみ isAvailable = false
 *
 * 生成経路を loaded() / failed() の 2 つに限定するため、
 * コンストラクタを private にしている。
 */
final readonly class Section
{
    /**
     * @param  list<ContentBlock>  $blocks
     */
    private function __construct(
        public SectionId $id,
        public string $title,
        public string $lead,
        public array $blocks,
        public bool $isAvailable,
    ) {}

    /**
     * @param  list<ContentBlock>  $blocks
     */
    public static function loaded(SectionId $id, string $title, string $lead, array $blocks): self
    {
        return new self($id, $title, $lead, $blocks, true);
    }

    public static function failed(SectionId $id): self
    {
        return new self($id, $id->defaultTitle(), '', [], false);
    }

    public function blockByKey(string $key): ?ContentBlock
    {
        foreach ($this->blocks as $block) {
            if ($block->key === $key) {
                return $block;
            }
        }

        return null;
    }

    public function hasBlocks(): bool
    {
        return $this->blocks !== [];
    }
}
