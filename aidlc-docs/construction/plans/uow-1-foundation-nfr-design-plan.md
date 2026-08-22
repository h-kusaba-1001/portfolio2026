# NFR Design Plan — UoW-1（基盤構築）

**目的**: NFR Requirements（`aidlc-docs/construction/uow-1-foundation/nfr-requirements/`）で
確定した 44 の要件を、設計パターンと論理コンポーネントに落とし込む。

**前提**: 方針は NFR Requirements で確定済み。本ステージで決めるのは**実現手段**。

---

## Part 1: 確認事項（回答が必要）

### カテゴリの適用判断

質問を作るにあたり、5 カテゴリを評価しました。

| カテゴリ | 適用 | 判断 |
|---|---|---|
| **Resilience Patterns** | **対象外** | 外部依存・永続データ・状態が存在しない。リトライ・サーキットブレーカ・フェイルオーバの対象が無い（Resiliency 拡張も無効: ADR-010）。唯一の失敗経路である「Markdown 読み込み失敗」は Application Design で設計済み |
| **Scalability Patterns** | 質問 4 | Lambda の自動スケールに委ねる方針は確定済み。残る論点は同時実行上限に達した時の挙動 |
| **Performance Patterns** | 質問 2 | キャッシュ戦略の具体（TTL と無効化）が未確定 |
| **Security Patterns** | 質問 1 | オリジン遮断の実現手段が未確定。**Lift の対応状況に不確実性がある** |
| **Logical Components** | 質問 3, 5 | エラーハンドリングとログ相関の構成要素が未確定 |

---

## Question 1
**オリジン遮断（NFR-S8 / ADR-012）の実現手段**をどうしますか？

**設計上の不確実性（先に共有します）**

ADR-012 は「CloudFront がオリジンリクエストに付与する共有シークレットヘッダを
Lambda 側で検証する」と決めました。しかし **Lift の `server-side-website` 構造が
オリジンカスタムヘッダの設定に対応しているか、実装前の時点で確証がありません。**
対応していない場合、CloudFront Distribution に手を入れる追加作業が発生します。

A) **CloudFront の Origin Custom Header + SSM Parameter Store（SecureString）**
   - Lift が対応していれば最短。シークレットは SSM に置き、`serverless.yml` から参照する
   - SSM Parameter Store の標準パラメータは**無料**（Secrets Manager は 1 シークレットあたり
     月 0.40 USD かかるため NFR-1 と衝突する。使わない）
   - **Lift が非対応だった場合、B に切り替える必要がある**

B) **CloudFront Function でヘッダを注入する**
   - Lift の拡張機能（`extensions`）で CloudFront Distribution に関数を関連付ける
   - CloudFront Functions は月 200 万リクエストまで無料
   - Lift の対応状況に依存しない

C) **設計では「共有シークレット方式」だけ確定し、注入手段は Infrastructure Design に委ねる**
   - Lambda 側のミドルウェア（`VerifyCloudFrontOrigin`）は先に確定できる
   - CloudFront 側の実装は、Lift の対応状況を実際に確認してから決める

X) Other (please describe after [Answer]: tag below)

[Answer]:X
実装が楽な方に倒したい。
ADR-012に引っ張られて難しいことをしようとしているように見えるので、それの履行を踏まえた検討案をいくつか出してください。

---

## Question 2
**キャッシュ戦略**（NFR-S9 / U1-PF-3, U1-PF-4）をどうしますか？

Q8 = C で「CloudFront のキャッシュを強めて Lambda 到達を減らす」方針は確定しています。
ここで決めるのは HTML の TTL と、デプロイ時の扱いです。

**前提**: 静的アセット（`/build/*`）は Vite がファイル名にハッシュを付けるため、
どの案でも 1 年キャッシュで問題ありません。論点は HTML です。

A) **HTML はキャッシュしない**（`no-cache`）
   - デプロイ結果が即座に反映される
   - 全リクエストが Lambda に到達するため、濫用対策としては同時実行上限のみが効く

B) **HTML を短時間キャッシュする（TTL 60 秒）**
   - 連続アクセスの大半を CloudFront が吸収する
   - デプロイ後、最大 60 秒は古い HTML が配信される

C) **HTML を長時間キャッシュし、デプロイ時に無効化する（TTL 1 日 + Invalidation）**
   - Lambda 到達が最も少ない
   - CloudFront の Invalidation は月 1,000 パスまで無料。`/*` 指定なら 1 パス扱い
   - デプロイ手順に無効化のステップが増える

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Question 3
**エラーページ**（NFR-S6 / U1-RL-1, U1-RL-2）をどう出しますか？

A) **Blade の静的エラーページ**（`resources/views/errors/500.blade.php` 等）
   - Inertia・React を経由しないため、JS のビルドが壊れていても表示される
   - サイト本体とデザインが分かれる

B) **Inertia のエラーページ**（React コンポーネント）
   - サイト本体とデザインが揃う
   - React のバンドルが読み込めない状況では表示できない

C) **CloudFront のカスタムエラーレスポンス**（S3 上の静的 HTML）
   - Lambda が完全に落ちていても表示される
   - 最も堅いが、管理対象が増える

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Question 4
**同時実行上限（U1-SC-2）に到達した場合の挙動**をどうしますか？

上限に達すると Lambda は 429（Too Many Requests）を返します。

A) **そのまま 429 を返す**
   - 追加実装なし

B) **CloudFront のカスタムエラーレスポンスで、静的な「混雑しています」ページを返す**
   - 閲覧者に状況が伝わる
   - Question 3 で C を選ぶ場合は仕組みを共有できる

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 5
**ログの相関 ID**（U1-OB-2 / SECURITY-03）をどう採りますか？

A) **Lambda のリクエスト ID を使う**（`$context->getAwsRequestId()` 相当）
   - CloudWatch Logs の REPORT 行と突き合わせられる
   - Bref 経由で取得する実装が必要

B) **Laravel 側で UUID を生成する**（ミドルウェアで採番）
   - 実装が単純。ローカルでも同じ挙動
   - Lambda のリクエスト ID とは別系統になる

C) **両方をログに含める**

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Part 1.5: Question 1 の再提示（ADR-012 の見直しを含む）

**ご指摘のとおりです。** 経緯を整理すると、難しくなった原因は選択の積み重ねにあります。

```
Q3 = B（ヘッダは CloudFront のみで付与）
  -> API Gateway 直アクセス時にヘッダが付かない経路が残る
  -> SECURITY-04 の非準拠（ブロッキング所見）
  -> Q3-a で解消手段を提示し、B（オリジン遮断）を選択
  -> ADR-012 として確定
  -> 実現手段（Origin Custom Header の注入）が Lift の対応状況に依存し、重い
```

**起点は Q3 = B です。** ここを変えれば、オリジン遮断そのものが不要になります。

### 重要な確認: 何が SECURITY-04 の要件か

SECURITY-04 の対象は「**HTML を返す全てのエンドポイント**」です。
静的アセット（JS / CSS / 画像）は HTML を返さないため、**対象外**です。

つまり **Laravel のミドルウェアでヘッダを付ければ、それだけで SECURITY-04 に準拠します。**
HTML を返す経路は必ず Lambda を通るためです。
CloudFront で付ける必要も、オリジンを遮断する必要もありません。

私が Q3-a で「オリジン遮断が根本的」と推したのは、
Q3 = B（CloudFront のみ）という前提を固定したうえでの話でした。
**前提ごと選び直せることを、あの時点で提示すべきでした。**

---

## Question 1-a（差し替え）
オリジン遮断とヘッダ付与の方式をどうしますか？

A) **Laravel のミドルウェアだけでヘッダを付与する。オリジン遮断はやらない**（最も実装が楽）
   - `SecurityHeaders` ミドルウェアを 1 つ作るだけ。CloudFront 側の設定は不要
   - HTML を返す経路は必ず Lambda を通るため、**SECURITY-04 に準拠する**
   - ローカルでも Feature テストでもヘッダを検証できる
   - **ADR-012 を改訂**し、オリジン遮断の決定を取り消す
   - **失うもの**: ① 静的アセットにはヘッダが付かない（SECURITY-04 の対象外なので問題なし）
     ② API Gateway の URL に直接アクセスされると CloudFront のキャッシュを迂回できる
     → Lambda の実行回数が増える経路が残る。ただし同時実行上限（U1-SC-2）で
       費用の上限は担保される

B) **ミドルウェアで付与し、CloudFront 側は Lift が標準で提供する範囲だけ使う**
   - Lift の `server-side-website` には `security` 系のオプションがあり、
     一部のセキュリティヘッダを扱える可能性がある（**要確認**）
   - 使えれば静的アセットにもヘッダが付く。使えなければ A と同じ結果になる
   - 実装量は A + 設定確認のみ

C) **現行の ADR-012 を維持する**（CloudFront でヘッダ付与 + オリジン遮断）
   - 最も堅い。オリジン迂回そのものを塞げる
   - 実装量が最大。Lift の対応状況次第で CloudFront Function の追加が必要

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 1-b
Question 1-a で A または B を選んだ場合、**オリジン迂回（API Gateway 直アクセス）** を
どう扱いますか？

A) **許容する**。同時実行上限（U1-SC-2）で費用の上限は担保されているため、追加対策はしない
   - 実装量ゼロ
   - 迂回されるとキャッシュが効かず Lambda 実行回数が増えるが、上限で頭打ちになる

B) **将来の課題として記録し、今はやらない**
   - ADR に「拡張ポイント」として残す。構成図の拡張ポイントに含める案もある

C) Question 1-a で C（ADR-012 維持）を選んだので、この質問は該当しない

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Part 2: 実行ステップ（回答後に実施）

- [x] 回答の分析（曖昧・矛盾がないか検証。あれば追加質問）
      → Q1 をユーザー指摘により差し戻し、Question 1-a / 1-b で再確認（1-a = A、1-b = B）
- [x] `aidlc-docs/construction/uow-1-foundation/nfr-design/nfr-design-patterns.md` を生成
      （適用パターン 6 件、適用しないパターン 11 件とその理由、既知の未対応事項 3 件）
- [x] `aidlc-docs/construction/uow-1-foundation/nfr-design/logical-components.md` を生成
      （論理コンポーネント 10 件、擬似コード、統合図、設定値一覧）
- [x] Application Design との整合性を検証
      → `VerifyCloudFrontOrigin` を `SecurityHeaders` に戻し、components.md / component-methods.md を更新
- [x] Security Compliance（SECURITY-01〜15）を評価
      → 準拠 9 / N/A 4 / 準拠（例外あり）1 / 部分的に適用外 1。ブロッキング所見なし
- [x] 新たな技術的決定が生じた場合は `docs/architecture-decisions.md` に ADR を追加
      → **ADR-015 を追加し、ADR-012 を Superseded に変更**
- [x] 上位要件の NFR-S8 を取り消しに更新
- [x] `aidlc-docs/aidlc-state.md` を更新
- [x] `aidlc-docs/audit.md` に記録
