# 技術構成

このポートフォリオサイトは、**AI-DLC** を用いて開発し、AWSのサーバレス構成で動作しています。
さらに、技術的な特徴としては、bref PHPを活用し、Lambda上でLaravelを動かしており、S3の静的コンテンツやCloudFrontにはServerless FrameworkのLiftプラグインを使用しています。

## CloudFront

[Serverless Lift](https://www.serverless.com/plugins/serverless-lift)の `server-side-website` 構造で構築。
静的アセットは S3 に置き、動的リクエストは Lambda に流しています。
CloudFront を手で組まずに済み、アセットの S3 アップロードも自動化されます。

独自ドメインは現段階はコスト観点で取得していません。

## S3

静的アセット（JavaScript・CSS）の置き場所です。

**CloudFront が URL のパスを見て、行き先を振り分けています。**
`/build/*` や `/aws-icons/*` は S3、それ以外は API Gateway へ。
S3 は聞かれたファイルを返すだけで、他所にリクエストを出すことはありません。

このバケットと CloudFront は**Serverless Framework（osls）の Lift プラグイン** で、
`server-side-website` の定義でビルドからアセットのアップロードまで行います。
**受託開発時代にサーバレス構成の設計と運用で培ったものを、そのまま使っています。**

## API Gateway

HTTP API のワイルドカードルートで、全リクエストを Lambda に流しています。

## Lambda (Bref)

PHP を Lambda で動かすための **Bref 3.0** を使用。
完全従量課金で、この規模なら無料枠に収まります。

**受託開発の現場で bref PHP と Laravel によるサーバレス API を作った経験を、
そのままこのサイトに活かしています。**

## Laravel + Inertia.js

LambdaでLaravelを動かし、markdownコンテンツをレンダリングをパースして、Inertia.jsがページコンポーネント名と props を返しています。

## デプロイ: osls

Serverless Framework は v4 から、個人利用でもアカウント登録かライセンスキーが必須になりました。
「固定費ゼロ」という前提と噛み合わないため、v3 の OSS フォークである osls を採用しています。

osls は Bref のメンテナが維持しており、Bref 公式ドキュメントも移行を推奨しています。

## 拡張ポイント

今後の拡張性を持たせています。

- **DynamoDB/RDS** — データ永続化が必要になったとき。従量課金のまま維持できます
- **Bref X-Ray** — コールドスタート監視などでトレースが必要になったとき
