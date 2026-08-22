<?php

declare(strict_types=1);

namespace App\Domain\Content;

/**
 * ページ 1 枚分の内容。
 *
 * 不変条件: SectionId の全ケースに対応する Section が必ず 1 つずつ存在する。
 * 読み込みに失敗した場合も failed() で埋まるため、要素数は常に 4。
 * 「セクションが消える」ことは起きない。
 */
final readonly class PortfolioContent
{
    /**
     * @param  list<Section>  $sections  表示順に並んでいること
     */
    public function __construct(
        private array $sections,
    ) {}

    /**
     * @return list<Section>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    public function section(SectionId $id): ?Section
    {
        foreach ($this->sections as $section) {
            if ($section->id === $id) {
                return $section;
            }
        }

        return null;
    }

    public function hasFailures(): bool
    {
        foreach ($this->sections as $section) {
            if (! $section->isAvailable) {
                return true;
            }
        }

        return false;
    }
}
