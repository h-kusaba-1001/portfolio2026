import type { ErrorPageProps } from '@/types';

/**
 * エラーページ（LC-6 / NFR-S6 / SECURITY-09）。
 *
 * 表示するのは固定文言のみ。例外メッセージ・スタックトレース・
 * ファイルパス・フレームワークのバージョンは一切出さない。
 */
const MESSAGES: Record<number, string> = {
    404: 'ページが見つかりません',
    429: 'アクセスが集中しています。時間をおいて再度お試しください',
    503: 'メンテナンス中です',
};

export default function Error({ status }: ErrorPageProps) {
    const message = MESSAGES[status] ?? 'エラーが発生しました';

    return (
        <main
            className="flex min-h-screen items-center justify-center bg-white px-6 text-slate-900"
            data-testid="error-page"
        >
            <div className="text-center">
                <p className="text-sm font-medium text-slate-400" data-testid="error-page-status">
                    {status}
                </p>
                <h1 className="mt-2 text-xl font-semibold" data-testid="error-page-message">
                    {message}
                </h1>
                <a
                    href="/"
                    className="mt-6 inline-block text-sm text-slate-500 underline underline-offset-4 hover:text-slate-900"
                    data-testid="error-page-home-link"
                >
                    トップへ戻る
                </a>
            </div>
        </main>
    );
}
