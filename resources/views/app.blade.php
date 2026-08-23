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
    @inertia
</body>
</html>
