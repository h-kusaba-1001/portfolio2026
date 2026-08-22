# NFR Requirements Clarification — UoW-1

## 検出した非準拠

**ご指定**: 各種アクセスログ・ログの保管期限を **14 日**とする。

**衝突するルール**: SECURITY-14（Alerting and Monitoring）

> **Log retention**: Logs MUST be retained for a minimum period appropriate to the
> application's compliance requirements (**default: 90 days minimum**)
>
> **Verification**: Application log groups have retention policies set (**minimum 90 days**)

Security ベースライン拡張は ADR-010 で **有効**にしています（`aidlc-docs/aidlc-state.md` の
Extension Configuration）。拡張ルールは「ハード制約」として扱う規定のため、
14 日への変更は **ブロッキング所見**となり、このままでは次ステージに進めません。

SECURITY-06（最小権限）はルール本文が「例外を文書化すること」を明示的に認めていますが、
**SECURITY-14 には例外規定がありません**。したがって「ADR に書けば済む」性質のものではなく、
拡張ルールそのものの適用範囲を変える判断になります。

---

## 判断材料

### 費用（14 日にする動機が費用の場合）

CloudWatch Logs の保存料は **$0.03 / GB・月**（`ap-northeast-1`）。
このサイトの規模でログが月 100 MB 出たと仮定すると:

| 保持期間 | 月額（概算） |
|---|---|
| 14 日 | 約 0.0015 USD（0.2 円程度） |
| 90 日 | 約 0.009 USD（1.4 円程度） |

**差は月 1 円程度**で、NFR-1（月額 100 円以下）には影響しません。
CloudFront アクセスログ（S3 保存）も同様に、この規模では差が数円に収まります。

**費用が動機であれば、14 日にする実利はほぼありません。**

### 調査可能な期間（14 日にした場合の実害）

障害や不審なアクセスに後から気付いた場合、**2 週間より前の記録は残っていません**。
個人サイトであれば許容できる可能性が高い一方、
「セキュリティを重視する構成」としてサイト上で説明する内容とは、やや逆方向の判断になります。

---

## Question 1
ログ保持期間をどうしますか？

A) **90 日を維持する**（変更しない）
   - SECURITY-14 に準拠したまま。費用差は月 1 円程度

B) **14 日にする。SECURITY-14 のログ保持要件のみを適用対象外とする**
   - Security 拡張の**部分的な無効化**にあたる
   - `aidlc-docs/aidlc-state.md` の Extension Configuration に「SECURITY-14 のログ保持は適用外」と記録し、
     ADR にも判断と理由を残す
   - SECURITY-14 の他の項目（ロググループ削除権限を与えない等）は引き続き適用する

C) **14 日と 90 日を使い分ける**
   - CloudWatch Logs（アプリケーションログ）: 14 日
   - CloudFront・API Gateway のアクセスログ（S3 保存）: 90 日
   - 「頻繁に見るログは短く、監査目的のログは長く」という整理。
     ただしアプリケーションログについては SECURITY-14 非準拠が残るため、
     B と同様に部分的な無効化の記録が必要

D) **30 日にする**（折衷案。B と同様に部分的な無効化の記録が必要）

X) Other (please describe after [Answer]: tag below)

[Answer]:B
個人開発、かつ、ポートフォリオのためコストを優先する

---

## Question 2
（Question 1 で B・C・D を選んだ場合のみ回答してください）

14 日（または 30 日）とする理由として、記録に残す内容はどれですか？
ADR とサイト上の説明の一貫性に関わります。

A) 費用の抑制（ただし上記のとおり差は月 1 円程度）

B) 個人ポートフォリオであり、長期の監査要件が存在しないため

C) 運用の簡素化（見返す可能性のない古いログを保持しない方針）

X) Other (please describe after [Answer]: tag below)

[Answer]:A,B,C
