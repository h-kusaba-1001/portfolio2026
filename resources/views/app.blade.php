<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{--
        SSR を導入していないため（ADR-008）、検索エンジンやリンクプレビューが
        読むのはこの Blade が出力する静的な meta のみ。ここだけは手で書く。
    --}}
    <title>HK Portfolio — 月額 100 円未満で動く、サーバレスなポートフォリオ</title>
    <meta name="description" content="AWS Lambda 上で動く Laravel + Inertia のポートフォリオサイト。データベースを持たず、固定費を限りなくゼロに近づけた構成と、その選定理由を公開しています。">

    <meta property="og:type" content="website">
    <meta property="og:title" content="HK Portfolio">
    <meta property="og:description" content="月額 100 円未満で動く、サーバレスなポートフォリオ。技術構成とその選定理由を公開しています。">
    <meta property="og:locale" content="ja_JP">
    <meta name="twitter:card" content="summary">

    {{--
        検索結果に出さない。
        **robots.txt で拒否はしない。** クロール自体を止めると、
        この noindex を読んでもらえず、かえって残り続けることがある。
        また robots.txt を見る AI のフェッチャまで弾いてしまい、
        プリレンダ（ADR-020）で得た「AI に読ませる」目的と衝突する。
        ヘッダ側にも X-Robots-Tag を出している（config/security.php）。
    --}}
    <meta name="robots" content="noindex, nofollow">

    {{--
        ファビコン。SVG に対応したブラウザは favicon.svg を、
        それ以外は .ico を使う。ブラウザが自動で取りに行く /favicon.ico は
        Lift の assets に明示しないと Lambda に流れて 404 になるため、
        serverless.yml 側にも 1 行入っている。
    --}}
    <link rel="icon" href="/brand/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="16x16 32x32 48x48">
    <link rel="apple-touch-icon" href="/brand/apple-touch-icon.png">

    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body class="antialiased">
    {{--
        ビルド時に描画した HTML を #app の中に入れる（A-1 / ADR-020）。

        JavaScript を実行しないクローラや AI に中身を届けるのが目的。
        React はマウント時にこの中身を捨てて描き直すため、
        表示結果は変わらない（同じ内容が描かれる）。

        $page は Inertia がルートビューに渡す変数で、@inertia が出すものと同じ。
        プリレンダが無いとき（ローカルなど）は従来どおり空の #app になる。
    --}}
    {{--
        @inertia が出すものと同じ形にすること。**Inertia v3 はページ情報を
        下の <script data-page="app"> から読む。** 以前ここを
        <div data-page="..."> だけにしてしまい、script が消えた結果、
        クライアントが null を掴んで画面が真っ白になった。
        構造は tests/Feature/InertiaRootTest.php が固定している。
    --}}
    <script data-page="app" type="application/json">{!! json_encode($page, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}</script><div id="app">{!! $prerenderedPage !!}</div>
</body>
</html>
