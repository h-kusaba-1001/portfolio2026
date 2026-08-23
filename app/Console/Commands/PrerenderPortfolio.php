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

        // body には Inertia が data-page の <script> も含めて返す。
        // その script は Blade 側が出すので、ここでは取り除く。
        // **残したまま埋めると、同じペイロードが 2 回出て HTML が倍近くなる。**
        $body = preg_replace('/<script data-page.*?<\/script>/s', '', $rendered['body']) ?? '';

        PrerenderedPage::store(trim($body));

        $this->info(sprintf('プリレンダを保存しました（%s 文字）', number_format(mb_strlen($body))));

        return self::SUCCESS;
    }
}
