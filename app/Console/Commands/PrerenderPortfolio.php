<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Content\GetPortfolioContent;
use App\Http\Presenters\PortfolioProps;
use App\Support\PrerenderedPage;
use Illuminate\Console\Command;
use Inertia\Inertia;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * トップページをビルド時に 1 回だけ描画して保存する（A-1 / ADR-020）。
 *
 * **実行時には走らない。** Lambda に Node は無い。
 * `bin/deploy.sh` がパッケージングの前に 1 回呼ぶだけ。
 *
 * 出力は React が本来描く HTML そのもの。Blade がこれを #app の中に
 * 埋めるため、JavaScript を実行しないクローラや AI にも中身が届く。
 */
final class PrerenderPortfolio extends Command
{
    protected $signature = 'portfolio:prerender';

    protected $description = 'トップページをビルド時に描画して保存する（AI・クローラ向け）';

    /**
     * SSR の出力から、#app の中身だけを取り出す。
     *
     * Inertia が返す body は
     *   <script data-page="app" ...>...</script><div id="app">中身</div>
     * という形。**この外側は Blade が出すので、ここでは中身だけを取る。**
     *
     * script を残すと同じペイロードが 2 回出て HTML が倍になる。
     * div を残すと id="app" が入れ子で 2 つになり、
     * Inertia がどちらを掴むか不定になる。
     */
    private function innerHtml(string $body): string
    {
        $inner = preg_replace('/\A.*?<div[^>]*id="app"[^>]*>/s', '', $body) ?? '';
        $inner = preg_replace('/<\/div>\s*\z/s', '', $inner) ?? '';

        return trim($inner);
    }

    public function handle(GetPortfolioContent $getPortfolioContent): int
    {
        $bundle = base_path('bootstrap/ssr/ssr.js');

        if (! file_exists($bundle)) {
            $this->error('SSR バンドルがありません。先に `npm run build` を実行してください。');

            return self::FAILURE;
        }

        $page = [
            'component' => 'Portfolio',
            // Inertia のミドルウェアが常に共有する prop。React 側は使っていないが、
            // 実行時のペイロードと形を揃えておく。
            'props' => [
                'errors' => (object) [],
                'sections' => PortfolioProps::sections($getPortfolioContent()),
            ],
            'url' => '/',
            'version' => Inertia::getVersion(),
        ];

        $input = tempnam(sys_get_temp_dir(), 'prerender');

        if ($input === false) {
            throw new RuntimeException('一時ファイルを作成できませんでした');
        }

        try {
            file_put_contents($input, json_encode($page, JSON_THROW_ON_ERROR));

            $process = new Process(['node', $bundle, $input], base_path(), timeout: 60);
            $process->run();

            if (! $process->isSuccessful()) {
                $this->error('描画に失敗しました:');
                $this->line($process->getErrorOutput());

                return self::FAILURE;
            }

            /** @var array{head: list<string>, body: string} $rendered */
            $rendered = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        } finally {
            @unlink($input);
        }

        $html = $this->innerHtml($rendered['body']);

        PrerenderedPage::store($html);

        $this->info(sprintf('プリレンダを保存しました（%s 文字）', number_format(mb_strlen($html))));

        return self::SUCCESS;
    }
}
