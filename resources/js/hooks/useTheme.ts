import { useCallback, useEffect, useState } from 'react';

export type Theme = 'light' | 'dark' | 'system';

const STORAGE_KEY = 'hk-portfolio-theme';

function readStoredTheme(): Theme {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        if (stored === 'light' || stored === 'dark' || stored === 'system') {
            return stored;
        }
    } catch {
        // プライベートモード等で localStorage が使えない場合は既定に倒す
    }

    return 'system';
}

function applyTheme(theme: Theme): void {
    const root = document.documentElement;

    root.classList.remove('light', 'dark');

    if (theme !== 'system') {
        root.classList.add(theme);
    }
}

/**
 * テーマの切り替え（Q1 = C: 自動 + 手動トグル）。
 *
 * 既定は `system`。CSS 側が prefers-color-scheme で自動的に追従するため、
 * 明示指定が無い限りクラスを付けない。
 *
 * CSP が script-src 'self' のためインラインスクリプトを使えず、
 * 明示指定した利用者では初回描画時に一瞬ちらつく可能性がある。
 * 既定（system）の利用者はちらつかない。
 */
export function useTheme(): { theme: Theme; setTheme: (theme: Theme) => void } {
    const [theme, setThemeState] = useState<Theme>('system');

    useEffect(() => {
        const stored = readStoredTheme();
        setThemeState(stored);
        applyTheme(stored);
    }, []);

    const setTheme = useCallback((next: Theme) => {
        setThemeState(next);
        applyTheme(next);

        try {
            localStorage.setItem(STORAGE_KEY, next);
        } catch {
            // 保存できなくても表示は切り替わる
        }
    }, []);

    return { theme, setTheme };
}
