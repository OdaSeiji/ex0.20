# 2026-09-26 押出日報：レイアウト整理・副担当者の追加・入力チェックの見直し

## 1. 入力チェックの見直し（press_daily_report.html）

- **計画ビレット数**（`plan-billet-qty__input`）：入力範囲を 1〜50 から **0〜50** の整数に変更。
- **量産時の生産指示書**（`directive__input`）：必須から外した。
  - 以前はプレス種別が「●」（量産）のときだけ必須で、欄が赤枠表示になっていた。
  - 「●」を選んだときに生産指示書の選択モーダルが自動で開く動きは残した（入力は任意）。
  - 未入力の場合、`t_press.ordersheet_id` は NULL で保存される。
- **押出指示書**（`press-directive__select`）は必須のまま。

## 2. 押出日報カードのレイアウト整理

カードの高さ（段数）を増やさずに、担当者の行を空けるための並べ替え。

```
1段目: 日付 | 金型番号 | 🍓 | 押出指示書
2段目: プレス種別 | 生産指示書 [選択] | 洗浄済み | 設備番号 | ビレットサイズ
3段目: ビレット長さ | 計画ビレット数 | 実績ビレット数 | プレス時間（開始 - 終了）
4段目: ラム速度 | 実測金型温度 | 実測長さ | ストレッチ
5段目: 担当者 | 副担当者
```

- プレス時間を4段目から3段目へ移動。
- ラム速度・実測金型温度は3〜4桁しか入らないため幅を詰め（100px）、空いた右側に実測長さ・ストレッチを移動。
  実測金型温度はベトナム語ラベル「Nhiệt độ khuôn thực tế (℃)」が1行に収まるよう 145px。

## 3. 副担当者（複数）の登録

### 目的
押出作業に複数メンバーが関わる場合に、主担当以外のメンバーも記録できるようにする。

### 画面
- 担当者（主担当）を約1/3幅にし、その右に「副担当者（人数）」のプルダウンを追加。
  - 選ぶとリストに追加され、プルダウンは「＋ 追加」に戻る。
  - 主担当・追加済みの人は選択肢で無効化。副担当者にいる人を主担当に選ぶと副担当者から外れる。
- 選んだ副担当者は、ビレット情報カードの下に独立させた **「副担当者」カード** に一覧表示（人数表示、✕で削除）。
- 実績一覧から編集で開くと副担当者も復元され、キャンセル・クリアで空になる。

### 保存
- 主担当は従来どおり `t_press.staff_id`（ex0.11 の日報もこの列を読んでいるため意味を変えない）。
- 副担当者は新設の中間テーブル `t_press_staff` に保存（主担当は含めない）。
- 更新時は、他のサブテーブルと同じく一旦削除してから入れ直す。
- 主担当と同じ人・重複指定・0 はサーバー側（`saveSubStaff()`）でも除外する。

### 対象ファイル
- `press_daily_report.html`
- `php/press_daily_report/press_common.php`（`saveSubStaff()` を追加）
- `php/press_daily_report/save_press.php`
- `php/press_daily_report/update_press.php`
- `php/press_daily_report/get_press_detail.php`（`subStaff` を返す）

## 4. DB変更

| SQL | ローカル | 本番 |
|---|---|---|
| `t_press_staff` の作成 | 実行済み | 実行済み |
| `idx_press_ordersheet` の追加 | 実行済み | 実行済み |
| `ANALYZE TABLE t_press` | 実行済み | 実行済み |
| `t_press_quality` のラック外部キーを CASCADE → RESTRICT | 実行済み | 実行済み |

### t_press_staff（副担当者）
既存の `t_packing_worker`（梱包の作業者）と同じ形。押出実績を削除すると副担当者も削除される。
**コードの反映より先に作成すること**（無いと押出日報の「更新」がすべてエラーになる）。

```sql
CREATE TABLE t_press_staff (
  id          INT(11) NOT NULL AUTO_INCREMENT,
  t_press_id  INT(11) NOT NULL,
  m_staff_id  INT(11) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_press_staff (t_press_id, m_staff_id),
  KEY m_staff_id (m_staff_id),
  CONSTRAINT t_press_staff_ibfk_1 FOREIGN KEY (t_press_id) REFERENCES t_press (id) ON DELETE CASCADE,
  CONSTRAINT t_press_staff_ibfk_2 FOREIGN KEY (m_staff_id) REFERENCES m_staff (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='押出実績の副担当者（主担当は t_press.staff_id）';
```

※ 日本語コメントを含むため、mysql コマンドラインでは引数に直接書かず、UTF-8 の SQL ファイルを
`mysql --default-character-set=utf8mb4 ... < file.sql` で流す（引数だと cp932 で文字化けする）。

### idx_press_ordersheet（t_press.ordersheet_id のインデックス）
2026-07-15 に `order_sheet.html` の一覧高速化のため追加したものが、2026-08-16 の DB 再構築で
ローカル・本番とも消えていたため再追加。未梱包ラック一覧 API（90日分）が約1.3秒 → 約0.12秒になった。
本番の `SHOW INDEX` で Cardinality がすべて 0 だったため、統計情報も更新した。

```sql
ALTER TABLE t_press ADD INDEX idx_press_ordersheet (ordersheet_id);
ANALYZE TABLE t_press;
```

### t_press_quality → t_using_aging_rack の外部キーを RESTRICT に変更
NG記録が付いたラックを削除すると、NG記録も CASCADE で一緒に消えていた（梱包・時効はもともと RESTRICT）。
ex0.11 のラック削除・押出日報削除、手作業の SQL など、どこから削除しても NG記録が消えないよう DB 側で止める。
詳細は「5.」を参照。

```sql
ALTER TABLE t_press_quality DROP FOREIGN KEY to_t_using_aging_rack_id;
ALTER TABLE t_press_quality
  ADD CONSTRAINT to_t_using_aging_rack_id
  FOREIGN KEY (using_aging_rack_id) REFERENCES t_using_aging_rack (id)
  ON DELETE RESTRICT ON UPDATE RESTRICT;
```

確認用：
```sql
SELECT CONSTRAINT_NAME, DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = 'extrusion' AND TABLE_NAME = 't_press_quality';
```

## 5. 押出日報の「更新」でラックに紐づくデータが壊れる問題（暫定対応済み）

### 問題
`update_press.php` は更新時に `t_using_aging_rack` を全削除して入れ直すため、ラックの `id` が変わる。
次の順で操作すると、2 の記録が失われる、または 3 ができなくなる。

1. 押出日報を入力する（ラックが登録される）
2. 品質評価（NG）・梱包・時効の記録を入力する（ラックの id に紐づく）
3. 押出日報を修正する（ラックを全削除 → 再登録）

| 参照元 | 変更前の削除時の動き | 3 のときに起きること |
|---|---|---|
| `t_press_quality`（NG記録） | CASCADE | **NG記録が警告なく削除される**（復元不可） |
| `t_packing_box`（梱包） | RESTRICT | 梱包済みラックがあると更新がエラーで失敗する |
| `t_aging`（時効） | RESTRICT | 時効済みラックがあると更新がエラーで失敗する |

2026年のラック 7,458 件のうち、NG記録ありが 59%、梱包記録ありが 79%。
NG記録は押出日の平均 5〜11 日後に入力されており、数日以上たった日報を修正するとほぼ確実に該当する。

ex0.11 の日報（DailyReportV31）はラックを1行ずつ追加・削除する作りで、この問題は起きない
（ex0.20 の押出日報で新たに入り込んだ問題。押出日報は現場未採用のため実害はまだないはず）。

また、ex0.11 で押出日報やラックを削除した場合も、t_press → ラック → NG記録 と CASCADE が連鎖して NG記録が消えていた。

### 暫定対応
- **アプリ側**：NG・梱包・時効の記録が1件でも付いている押出日報は修正できないようにした。
  - 編集で開くと「🔒 品質記録(NG) n件・梱包 n件・時効 n件 の記録があるため修正できません」と表示し、更新ボタンを無効化。
  - `update_press.php` でも更新前に件数を確認し、記録があれば何も変更せず 409 を返す。
  - 記録がまだない押出日報（入力直後の打ち間違いなど）は従来どおり修正できる。
  - 対象ファイル：`press_common.php`（`pressEditLocks()`）、`update_press.php`、`get_press_detail.php`、`press_daily_report.html`
- **DB側**：NG記録の外部キーを RESTRICT に変更（「4. DB変更」参照）。
  - ex0.11 で NG記録付きのラック・押出日報を削除しようとすると「DB connect error」が出て削除されない
    （梱包済みラックを削除しようとしたときと同じ動き）。削除したい場合は先に品質記録画面で NG を消す。

### 今後
- 押出日報を「どんなときに・何を」修正する必要があるのか、使い方を把握してから本対応を決める。
- 本対応の案：ラックの DB の id を画面に持たせ、id で対応づけた差分更新
  （id がある行は UPDATE、新しい行は INSERT、画面から消えた行は DELETE）にする。
  `order_number` での対応づけは、途中の行を消すと順番がずれて別のラックと取り違えるため不可。

### 参考：外部キーの重複（未整理）
同じ列に外部キーが2本ずつ付いているテーブルがある。今回の問題には直接関係しないため未対応。
- `t_using_aging_rack.t_press_id`：`FK_t_using_aging_rack_t_press` と `fk_usingagingrack_press`
- `t_press_work_length_quantity.press_id`：CASCADE と RESTRICT の2本（ルールが矛盾）
