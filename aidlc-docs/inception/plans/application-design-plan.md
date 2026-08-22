# Application Design Plan

**目的**: 主要コンポーネントの識別、責務の割り当て、インターフェースの定義、依存関係の確立。
詳細なビジネスロジックは CONSTRUCTION フェーズの Functional Design（UoW-2）で扱う。

**入力**: `aidlc-docs/inception/requirements/requirements.md`、`docs/requirements.md`、
`docs/architecture-decisions.md`（ADR-001〜010）、`docs/aidlc-inception.md`（US-1〜7、UoW-1〜4）

---

## Part 1: 設計判断の確認（回答が必要）

以下の `[Answer]:` に選択肢の記号を記入してください。
どれも当てはまらない場合は最後の選択肢を選び、内容を記述してください。

### 背景: 現状の把握

`content/` 配下に存在するファイルと、`docs/requirements.md` §5 のサイト構成の対応:

| セクション | 対応する content ファイル |
|---|---|
| S-1. Hero | **なし** |
| S-2. 技術構成 | `content/stack.md` |
| S-3. やってきたこと | `content/experience.md` |
| S-4. キャリアの変遷 | `content/career.md` |
| S-5. これから | `content/next.md` |
| S-6. Contact | **なし** |

---

## Question 1
Hero（S-1）と Contact（S-6）のテキストをどこに置きますか？
US-7「コードを触らずにコンテンツを更新したい」との整合が論点です。

A) `content/hero.md` と `content/contact.md` を新規作成し、全セクションを Markdown に統一する

B) Hero と Contact は分量が少ないため React コンポーネントに直接書く（US-7 の対象外とする）

C) Hero と Contact は `content/site.md` にまとめ、1 ファイルで扱う

X) Other (please describe after [Answer]: tag below)

[Answer]:B
さらに、contact.mdというか連絡先セクション・S-6. Contactは不要。
Heroの周辺に、以下のリンクをGitHubっぽく設置して
https://github.com/h-kusaba-1001/portfolio2026

---

## Question 2
構成図（S-2）のノードと `content/stack.md` の見出しの対応付けをどうしますか？
現在 `stack.md` には `## CloudFront` `## API Gateway` `## Lambda (Bref)` `## Laravel + Inertia.js`
`## デプロイ: osls` `## 拡張ポイント` という H2 見出しがあります。

A) H2 見出しのテキストをノード名として規約で対応付ける（Markdown 側に追加記述は不要。見出しを変えると対応が壊れる）

B) 各 H2 の直下に front matter 風のメタ情報（`node: cloudfront` など）を書き、明示的に対応付ける

C) ノードの定義（ID・座標・接続関係）は PHP の設定ファイルに持ち、Markdown は本文のみを供給する。設定ファイル側が Markdown の見出しを参照する

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 3
Markdown のパースをいつ行いますか？（コールドスタート時間と費用に影響します）

A) リクエストごとにパースする（キャッシュなし。最もシンプル）

B) Laravel のキャッシュに載せる（Lambda のコンテナが生きている間は再利用。ファイルキャッシュ or APCu）

C) デプロイ時に事前パースして PHP 配列としてビルドし、実行時は読み込むだけにする

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Question 4
ページのルーティング構成をどうしますか？

A) 単一ページ（`/` のみ）。全セクションを 1 スクロールに並べる

B) セクションごとにルートを分ける（`/`, `/stack`, `/career` など）

C) 単一ページ + 印刷/共有用の個別ルートを一部だけ用意する

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Question 5
Markdown を読み込むサーバ側の構造をどうしますか？

A) 単一のサービスクラス（例: `ContentRepository`）が全ファイルの読み込みとパースを担当し、コントローラはそれを呼ぶだけ

B) セクションごとに専用クラスを作る（`StackContent`, `CareerContent` など）

C) コントローラ内で直接読み込む（クラスを増やさない）

X) Other (please describe after [Answer]: tag below)

[Answer]:A
例えば、MarkdownParserってのをServiceクラスに作って、読むとかのイメージ
クリーンアーキテクチャっぽくなるように、上記に固執せず、良い感じにしてください

---

## Question 6
`content/*.md` が欠損・破損していた場合の挙動をどうしますか？
（SECURITY-15「フェイルクローズ」「利用者に内部情報を出さない」に関わります）

A) 例外を投げて 500 を返す（本番では汎用エラーページ。異常に気付ける）

B) そのセクションを非表示にしてページ全体は表示する（部分的な劣化を許容）

C) ビルド時／デプロイ前のチェックで検出し、実行時は必ず存在する前提にする

X) Other (please describe after [Answer]: tag below)

[Answer]:B
パースに失敗しましたのメッセージを表示するようにしてください

---

## Question 7
構成図 SVG（`ArchitectureDiagram`）の実装方針をどうしますか？

A) ノードと矢印の座標をコンポーネント内にハードコードした手書き SVG（レイアウトを完全に制御できる。ノード追加時は手作業）

B) ノード配列（ID・ラベル・座標・接続先）をデータとして持ち、SVG を生成する（拡張ポイントの追加が容易。レイアウト調整の自由度は下がる）

C) レイアウトライブラリを導入して自動配置する

X) Other (please describe after [Answer]: tag below)


[Answer]:A
Aにするか

---

## Question 8
React コンポーネントの粒度をどうしますか？

A) セクションごとに 1 コンポーネント（`Hero`, `Stack`, `Experience`, `Career`, `Next`, `Contact`）+ `ArchitectureDiagram` を分離

B) 上記に加えて、共通要素（`Section`, `MarkdownBlock`, `NodePanel` など）を細かく分ける

C) ページ全体を 1 コンポーネントにまとめ、`ArchitectureDiagram` のみ分離する

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Part 1.5: 追加質問（回答の分析で検出した曖昧さ）

回答は概ね明確でしたが、2 点だけ設計が大きく分岐するため確認します。

---

## Question 5-a（Q5 のフォローアップ）

Q5 で「クリーンアーキテクチャっぽくなるように、良い感じに」とのことでした。
ここで層をどこまで作るかを決めないと、コンポーネント設計全体が変わります。

**論点**: ADR-004 は「要件が無い層は作らない」と定めています。一方でクリーンアーキテクチャは
層とインターフェースを増やす方向に働きます。このサイトのドメインは
「Markdown を読んで HTML に変換する」だけで、業務ルールも永続化も外部連携もありません。
層を厚くするほど、ADR-004 の主張と実装が食い違います。

以下のどの深さにしますか。

A) **Laravel 標準に寄せた薄い構成**（層は増やさない）

```
app/
  Http/Controllers/PortfolioController.php
  Services/MarkdownParser.php          # CommonMark のラッパ
  Services/ContentRepository.php       # ファイル読み込み + パース + キャッシュ
  Data/SectionContent.php              # DTO（readonly class）
```

B) **軽量な 3 層 + インターフェース**（依存の向きだけ制御する。推奨）

```
app/
  Domain/Content/
    Section.php                        # エンティティ相当（readonly）
    SectionId.php                      # 値オブジェクト（enum）
    ContentRepositoryInterface.php     # ポート（Domain 側が所有）
  Application/Content/
    GetPortfolioContent.php            # ユースケース。全セクションを組み立てる
  Infrastructure/Content/
    MarkdownContentRepository.php      # アダプタ。ファイル読み込み + パース + キャッシュ
    CommonMarkParser.php               # MarkdownParser の実装
  Http/Controllers/PortfolioController.php
```

C) **フルなクリーンアーキテクチャ**（Entity / UseCase / Port / Adapter / Presenter を全て分ける）

```
app/
  Domain/           # Entity, ValueObject, RepositoryInterface, DomainService
  Application/      # UseCase, InputPort, OutputPort, InputData, OutputData
  Infrastructure/   # Repository実装, Parser実装, Cache実装
  Presentation/     # Controller, Presenter, ViewModel
```

X) Other (please describe after [Answer]: tag below)

[Answer]:B

---

## Question 6-a（Q6 のフォローアップ）

Q6 の回答は「B) そのセクションを非表示にする」と「パースに失敗しましたのメッセージを表示する」の
両方でした。この 2 つは動作が異なるため、どちらかに寄せる必要があります。

なお、どの案でも詳細なエラー内容（ファイルパス・スタックトレース）は画面に出さず、
CloudWatch Logs にのみ記録します（NFR-S6 / SECURITY-09, 15）。

A) セクションの枠は残し、本文の位置に「コンテンツを読み込めませんでした」と表示する
   （欠落が利用者からも自分からも見える）

B) 本番では該当セクションを完全に非表示にし、ローカル開発時のみエラーメッセージを表示する
   （公開サイトに不備を見せない）

C) セクションは非表示にし、画面のどこにもメッセージを出さない（ログのみ）

X) Other (please describe after [Answer]: tag below)

[Answer]:A

---

## Part 2: 実行ステップ（回答後に実施）

回答を受け取り、曖昧さがないか検証したうえで以下を生成します。

- [ ] 回答の分析（曖昧・矛盾・複数選択肢の混在がないか検証。あれば追加質問）
- [ ] `aidlc-docs/inception/application-design/components.md` を生成
      （コンポーネント名、目的、責務、インターフェース）
- [ ] `aidlc-docs/inception/application-design/component-methods.md` を生成
      （メソッドシグネチャ、目的、入出力型。詳細なビジネスルールは Functional Design で扱う）
- [ ] `aidlc-docs/inception/application-design/services.md` を生成
      （サービス定義、責務、オーケストレーション）
- [ ] `aidlc-docs/inception/application-design/component-dependency.md` を生成
      （依存マトリクス、通信パターン、データフロー図）
- [ ] `aidlc-docs/inception/application-design/application-design.md` を生成
      （上記を統合した 1 枚のドキュメント）
- [ ] 設計の完全性と一貫性を検証
- [ ] Security Compliance（SECURITY-11「セキュア設計」ほか該当ルール）を評価
- [ ] `aidlc-docs/aidlc-state.md` を更新
- [ ] `aidlc-docs/audit.md` に記録
