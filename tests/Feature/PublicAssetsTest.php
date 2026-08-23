<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

/*
| 静的ファイルの配信設定。
|
| CloudFront は Lift の `assets` に書かれたパスだけを S3 に向ける。
| 書き忘れたパスは Lambda に流れ、Bref の FPM ハンドラが静的ファイルを
| 返さないため **本番でだけ 404** になる。ローカルでは Laravel の
| public ディレクトリから配信されてしまうので気づけない。
|
| 実際に /aws-icons/* と /brand/* で 2 回踏んでいるため、機械で止める。
*/

/**
 * serverless.yml の assets 定義を読む。
 *
 * `!GetAtt` などの CloudFormation 独自タグが含まれるため、
 * PARSE_CUSTOM_TAGS を付けないとパースが失敗する。
 *
 * @return array<string, string>
 */
function serverlessAssets(): array
{
    /** @var array{constructs: array{website: array{assets: array<string, string>}}} $config */
    $config = Yaml::parse(
        file_get_contents(base_path('serverless.yml')),
        Yaml::PARSE_CUSTOM_TAGS,
    );

    return $config['constructs']['website']['assets'];
}

/**
 * app.blade.php が参照しているルート相対のパスを集める。
 *
 * @return list<string>
 */
function referencedAssetPaths(): array
{
    $blade = file_get_contents(resource_path('views/app.blade.php'));

    preg_match_all('/(?:href|src)="(\/[^"]+)"/', $blade, $matches);

    return array_values(array_unique($matches[1]));
}

it('blade が参照する静的ファイルは実在する', function () {
    foreach (referencedAssetPaths() as $path) {
        expect(file_exists(public_path(ltrim($path, '/'))))
            ->toBeTrue("public{$path} が存在しない");
    }
});

it('blade が参照する静的ファイルは CloudFront から S3 に向いている', function () {
    $patterns = array_keys(serverlessAssets());

    foreach (referencedAssetPaths() as $path) {
        $covered = false;

        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $path)) {
                $covered = true;
                break;
            }
        }

        expect($covered)->toBeTrue(
            "{$path} が serverless.yml の assets に無い。".
            'このままだと Lambda に流れて本番で 404 になる'
        );
    }
});

it('assets に書いたローカルパスが実在する', function () {
    foreach (serverlessAssets() as $pattern => $localPath) {
        expect(file_exists(base_path($localPath)))
            ->toBeTrue("{$pattern} の配信元 {$localPath} が存在しない");
    }
});
