<?php

declare(strict_types=1);

namespace App\Domain\Content;

/**
 * 掲載セクションの識別子。
 *
 * ファイル名・既定タイトル・表示順を一箇所に閉じ込める。
 * ここに無いファイルは読まない（business-rules.md R-7）。
 * ファイル名を外部入力から組み立てる経路が存在しないため、
 * パストラバーサルの余地がない。
 *
 * Hero（S-1）は Markdown を持たないため含まない。
 */
enum SectionId: string
{
    case STACK = 'stack';
    case EXPERIENCE = 'experience';
    case CAREER = 'career';
    case NEXT = 'next';

    public function fileName(): string
    {
        return $this->value.'.md';
    }

    /**
     * H1 が読めなかった場合のフォールバック（R-1-2）。
     * 通常は Markdown の H1 が表示される。
     */
    public function defaultTitle(): string
    {
        return match ($this) {
            self::STACK => '技術構成',
            self::EXPERIENCE => 'やってきたこと',
            self::CAREER => 'キャリアの変遷',
            self::NEXT => 'これから',
        };
    }

    /**
     * 表示順（R-5-1）。docs/requirements.md §5 の S-2〜S-5 に対応する。
     * 技術構成を経歴より前に置くのは、技術力が最大の訴求軸であるため。
     *
     * @return list<self>
     */
    public static function inDisplayOrder(): array
    {
        return [self::STACK, self::EXPERIENCE, self::CAREER, self::NEXT];
    }
}
