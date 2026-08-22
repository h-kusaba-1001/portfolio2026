# Code Generation Plan — UoW-1（基盤構築）

**このドキュメントが Code Generation の唯一の正典です。** 記載のないことは実行しません。

**対象 Bolt**: B-1（ローカルで Laravel + Inertia が起動）、B-2（デプロイして CloudFront で公開）

---

## 1. ユニットのコンテキスト

### 実装するストーリー

UoW-1 は基盤構築であり、**直接対応するユーザーストーリーはありません**
（`docs/aidlc-inception.md` §3 の対応表でも「—」）。
US-1〜US-7 の全てが UoW-1 の上に乗るため、**土台としての完成度が全ストーリーに影響します。**

### 依存関係

- **前提となるユニット**: なし（最初のユニット）
- **このユニットに依存するユニット**: UoW-2、UoW-3、UoW-4（全て）

### このユニットが提供するインターフェース

| 提供物 | 利用者 |
|---|---|
| `PortfolioController`（雛形） | UoW-2 が `GetPortfolioContent` を注入する |
| `Pages/Portfolio`（雛形） | UoW-3・UoW-4 がセクションを追加する |
| `SectionProps` 型（空定義） | UoW-2 が形を確定させる |
| Sail 環境 / `serverless.yml` | 全ユニットの開発・デプロイ基盤 |

### 所有するデータ

**なし**（ADR-002。データベース・マイグレーションを作成しません）。

---

## 2. 既存ファイルの保護（最重要）

ワークスペース直下に Laravel を展開します（Q3 = A）が、
**以下は絶対に上書き・削除しません。**

```
README.md              ← Laravel スケルトンにも同名ファイルがあるため特に注意
CLAUDE.md
docs/
content/
aidlc-docs/
.aidlc-rule-details/
.git/
```

**手順**: 一時ディレクトリに Laravel を生成し、**上記を除外して**ワークスペース直下へ移動します。
`.gitignore` は Laravel のものを採用しつつ、既存の運用に必要な行を追記します。

**各ステップの前に `git status` を確認し、意図しない削除が発生していないことを検証します。**

---

## 3. コードの配置先

| 種別 | 配置先 |
|---|---|
| アプリケーションコード | ワークスペース直下（`app/`, `resources/`, `routes/`, `tests/`, `config/`） |
| ビルド・設定ファイル | ワークスペース直下（`composer.json`, `package.json`, `compose.yaml`, `serverless.yml`, `vite.config.ts`） |
| ドキュメント（Markdown 要約のみ） | `aidlc-docs/construction/uow-1-foundation/code/` |

---

## 4. 実行ステップ

### Step 1: プロジェクト構造のセットアップ
- [ ] 1-1. `git status` で作業ツリーがクリーンであることを確認する
- [ ] 1-2. Docker 経由で Laravel スケルトンを一時ディレクトリに生成する
      （ホストに PHP / Composer が無いため。ADR-007 の手順と同じ方式）
- [ ] 1-3. 生成された Laravel のバージョンを記録する
- [ ] 1-4. **§2 の保護対象を除外して**ワークスペース直下へ移動する
- [ ] 1-5. `.gitignore` を配置する（Laravel のものをベースに、必要な行を追記）
- [ ] 1-6. `git status` で保護対象が失われていないことを確認する

### Step 2: Sail 環境の構築（B-1）
- [ ] 2-1. `laravel/sail` を dev 依存に追加する
- [ ] 2-2. Sail を PHP 8.4 で初期化する（ADR-007）
- [ ] 2-3. `compose.yaml` からデータベース・Redis 等のサービスを除去し、
        **アプリケーションコンテナのみ**にする（ADR-002）
- [ ] 2-4. **Docker イメージのタグをバージョン固定する**（`latest` を使わない。NFR-S5 / SECURITY-10）
- [ ] 2-5. `.env.example` を作成し、`.env` を生成してアプリケーションキーを設定する
- [ ] 2-6. `sail up -d` でコンテナが起動することを確認する

### Step 3: フロントエンド基盤（Inertia + React + TypeScript + Tailwind）
- [ ] 3-1. `inertiajs/inertia-laravel` を追加する
- [ ] 3-2. `@inertiajs/react`, `react`, `react-dom` を追加する
- [ ] 3-3. TypeScript と型定義を追加し、`tsconfig.json` を作成する（ADR-006）
- [ ] 3-4. Tailwind CSS と Vite プラグインを追加する
- [ ] 3-5. `vite.config.ts` を作成する
- [ ] 3-6. `resources/js/app.tsx` に Inertia のエントリポイントを作成する
- [ ] 3-7. `resources/js/types/index.ts` を作成する（`SectionProps` は UoW-2 で確定するため空定義）
- [ ] 3-8. **認証スキャフォールドを導入しない**ことを確認する（ADR-004 / SECURITY-09）

### Step 4: Blade テンプレート（OGP・meta の静的出力）
- [ ] 4-1. `resources/views/app.blade.php` を作成する
- [ ] 4-2. OGP・meta description・title を静的に出力する（ADR-008）
- [ ] 4-3. `lang` 属性と viewport を設定する（NFR-4）

### Step 5: セキュリティヘッダ（LC-1 / LC-2）
- [ ] 5-1. `config/security.php` を作成し、5 種のヘッダを定義する
      （CSP は ADR-011 の内容。`nfr-requirements.md` §4 の値をそのまま使う）
- [ ] 5-2. `app/Http/Middleware/SecurityHeaders.php` を作成する
- [ ] 5-3. `bootstrap/app.php` でミドルウェアを登録する（**例外経路も通る位置に置く**）

### Step 6: 相関 ID とログ（LC-3 / LC-4）
- [ ] 6-1. `app/Http/Middleware/RequestId.php` を作成する
      （Lambda リクエスト ID を優先し、取得できなければ UUID にフォールバック）
- [ ] 6-2. `config/logging.php` に JSON 形式の `stderr` チャンネルを設定する（U1-OB-1）
- [ ] 6-3. ミドルウェアを登録する

### Step 7: 例外ハンドラとエラーページ（LC-5 / LC-6）
- [ ] 7-1. `bootstrap/app.php` に例外ハンドラを設定する
      （本番は Inertia のエラーページ、ローカルは既定のデバッグ画面）
- [ ] 7-2. `resources/js/Pages/Error.tsx` を作成する
      （ステータスに応じた固定文言のみ。内部情報を出さない: NFR-S6）

### Step 8: ルーティングとコントローラ（雛形）
- [ ] 8-1. `app/Http/Controllers/PortfolioController.php` を作成する
      （**UoW-2 の `GetPortfolioContent` は未実装のため、この時点では空の props を返す**）
- [ ] 8-2. `routes/web.php` に `/` を定義する
- [ ] 8-3. `resources/js/Pages/Portfolio.tsx` を作成する（動作確認用の最小表示）
- [ ] 8-4. **Laravel の既定ウェルカムページを削除する**（SECURITY-09）

### Step 9: テスト（ADR-009）
- [ ] 9-1. Pest を設定する
- [ ] 9-2. Feature テスト: `/` が 200 を返し、Inertia のページが描画される
- [ ] 9-3. Feature テスト: `config('security.headers')` の全ヘッダがレスポンスに存在する（NFR-S1）
- [ ] 9-4. Feature テスト: 存在しないパスで内部情報が漏れない（NFR-S6）
- [ ] 9-5. `sail test` が通ることを確認する

### Step 10: デプロイ設定（B-2 の準備）
- [ ] 10-1. `bref/bref` と `bref/laravel-bridge` を追加する
- [ ] 10-2. `serverless.yml` を作成する
      （`infrastructure-design.md` §3 の内容。Lift の `extensions`、ログ用 S3、Budgets を含む）
- [ ] 10-3. `serverless-lift` プラグインを追加する
- [ ] 10-4. **D-2 の確認**: `bref/laravel-bridge` が `/tmp` 関連をどこまで自動処理するかを調べ、
        必要な環境変数のみを `serverless.yml` に設定する
- [ ] 10-5. `osls` を dev 依存に追加する

### Step 11: サプライチェーン対応（NFR-S5 / SECURITY-10）
- [ ] 11-1. `composer.lock` と `package-lock.json` がコミット対象であることを確認する
- [ ] 11-2. `composer audit` と `npm audit` を実行し、結果を記録する
- [ ] 11-3. CycloneDX による SBOM 生成の手順を用意する
- [ ] 11-4. **D-3 の確認**: 本番デプロイ時に開発用依存を除外する手順を確定する

### Step 12: ローカル動作確認（B-1 の完了判定）
- [ ] 12-1. `sail up -d` と `sail npm run dev` でページが表示されることを確認する
- [ ] 12-2. `sail test` が通ることを確認する
- [ ] 12-3. ブラウザでセキュリティヘッダが付いていることを確認する

### Step 13: デプロイ（B-2）— **ユーザーが実行**
- [ ] 13-1. **`BUDGET_ALERT_EMAIL`（D-5）を確認する**
- [ ] 13-2. デプロイ用 IAM ポリシーの初期版を用意する（D-4。不足は実行しながら詰める）
- [ ] 13-3. **ユーザーが `osls deploy --stage prod` を実行する**
        （AWS 認証情報はユーザーの環境にあるため。私は実行しません）
- [ ] 13-4. **D-1 の確認**: Lift の `extensions` が `DistributionConfig.Logging` を
        期待どおりマージしたかを、生成された CloudFormation テンプレートで検証する
- [ ] 13-5. `deployment-architecture.md` §4 の検証項目 V-1〜V-10 を実施する

### Step 14: ドキュメント更新
- [ ] 14-1. `README.md` のセットアップ手順を、実際に動いた手順へ更新する
      （**イメージタグの `latest` を固定タグへ修正する**）
- [ ] 14-2. 公開 URL を README に記載する
- [ ] 14-3. 実際に採用した Laravel のバージョンを記録する

### Step 15: 生成物の要約
- [ ] 15-1. `aidlc-docs/construction/uow-1-foundation/code/implementation-summary.md` を作成する
      （生成ファイル一覧、要件との対応、D-1〜D-5 の確認結果）
- [ ] 15-2. 設計と実装が食い違った箇所を記録する
- [ ] 15-3. 必要に応じて ADR を追加・更新する

### Step 16: 進捗の更新
- [ ] 16-1. `aidlc-docs/aidlc-state.md` を更新する
- [ ] 16-2. `aidlc-docs/audit.md` に記録する

---

## 5. 実行環境の制約（要判断）

**計画作成後に環境を確認したところ、私は Docker を実行できないことが判明しました。**

```
$ docker run --rm alpine:3.20 ...
docker: permission denied while trying to connect to the Docker daemon socket
        at unix:///var/run/docker.sock

$ id -nG
kusaba adm dialout cdrom floppy sudo audio dip video plugdev users netdev
          ^ docker グループに所属していない

$ ls -l /var/run/docker.sock
srw-rw---- 1 root docker 0 ...    ← docker グループのみアクセス可能

$ sudo -n true
sudo: a password is required       ← パスワード無しの sudo も不可
```

**影響範囲**: Step 1-2（Laravel の生成）、Step 2（Sail 起動）、Step 9-5・12（テスト実行）、
Step 11-2（`composer audit` / `npm audit`）— **Docker を使う全ての手順が実行できません。**

ホストに PHP・Composer が無いため（ADR-007 の前提）、Docker 以外の代替もありません。

---

## Question 1
この制約をどう解消しますか？

A) **Docker グループに追加する**（推奨）
   ```bash
   sudo usermod -aG docker $USER
   ```
   実行後、**WSL の再起動またはログインし直しが必要**（グループはログイン時に決まるため）。
   以降、私が Step 1〜12 を実行し、動作とテストまで検証できます。

B) **Docker を使う手順はユーザーが実行する**
   私がコマンドを提示し、ユーザーがこのセッションで `! <コマンド>` を使って実行する。
   出力が会話に入るので、私が結果を読んで次に進めます。
   手数は増えますが、環境を変更せずに済みます。

C) **私はファイルを書くだけにする**（実行・検証なし）
   `composer.json` / `package.json` / 設定ファイル一式を手書きで用意し、
   ユーザーが後でまとめて `sail up` と `sail test` を実行する。
   **私が動作を確認できないため、初回起動時にエラーが出る可能性が高くなります。**

X) Other (please describe after [Answer]: tag below)

[Answer]:A
実行しました

---

## 6. 実行方針

### 私が実行すること
- Step 1〜12、14〜16（**Question 1 の回答による**）
- 設計との突き合わせ、生成物の要約、進捗の更新

### ユーザーに実行してもらうこと
- **Step 13-3 のデプロイ**（`osls deploy --stage prod`）
  - AWS 認証情報がユーザーの環境にあるため
  - **外部に公開される、巻き戻しに手間のかかる操作**であるため、実行はユーザーの手で

### 中断のルール
- 設計と実装が食い違った場合、**勝手に設計を曲げず、報告して判断を仰ぎます**
- 実行できない手順があった場合、**できたふりをせず**その旨を報告します

---

## 7. 完了条件

- [ ] Step 1〜16 の全チェックボックスが `[x]` になっている
- [ ] `sail test` が通る
- [ ] ローカルでページが表示される（B-1）
- [ ] 公開 URL でページが表示される（B-2）
- [ ] 検証項目 V-1〜V-10 の結果が記録されている
- [ ] D-1〜D-5 の確認結果が記録されている
