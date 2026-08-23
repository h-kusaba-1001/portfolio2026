import { type Theme, useTheme } from '@/hooks/useTheme';

const OPTIONS: { value: Theme; label: string }[] = [
    { value: 'light', label: 'ライト' },
    { value: 'system', label: '自動' },
    { value: 'dark', label: 'ダーク' },
];

/**
 * テーマの切り替え（Q1 = C）。
 * 既定は「自動」で、OS の設定に従う。
 */
export default function ThemeToggle() {
    const { theme, setTheme } = useTheme();

    return (
        <div
            className="flex items-center gap-1 rounded-full border border-[color:var(--border)] p-0.5"
            role="group"
            aria-label="配色の切り替え"
            data-testid="theme-toggle"
        >
            {OPTIONS.map((option) => (
                <button
                    key={option.value}
                    type="button"
                    onClick={() => setTheme(option.value)}
                    aria-pressed={theme === option.value}
                    className={
                        'rounded-full px-2.5 py-1 text-xs transition-colors ' +
                        /*
                            選択状態を彩度の高い塗りにすると、ページで一番強い色が
                            設定コントロールになってしまう。面と文字色で示す。
                        */
                        (theme === option.value
                            ? 'bg-[color:var(--surface)] font-medium text-[color:var(--accent)]'
                            : 'text-[color:var(--fg-muted)] hover:text-[color:var(--fg)]')
                    }
                    data-testid={`theme-toggle-${option.value}`}
                >
                    {option.label}
                </button>
            ))}
        </div>
    );
}
