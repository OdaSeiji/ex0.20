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

## 5. 既知の問題（未対応）：押出日報の「更新」でラックに紐づくデータが壊れる

`update_press.php` は更新時に `t_using_aging_rack` を全削除して入れ直すため、ラックの `id` が変わる。
`t_using_aging_rack` を参照している外部キーは次のとおり。

| 参照元 | 削除時 | 更新時に起きること |
|---|---|---|
| `t_press_quality`（NG記録） | CASCADE | **NG記録が警告なく削除される** |
| `t_packing_box`（梱包） | RESTRICT | 梱包済みラックがあると更新がエラーで失敗する |
| `t_aging`（時効） | RESTRICT | 時効済みラックがあると更新がエラーで失敗する |

対策案：ラックだけは全削除をやめ、`order_number` をキーにした差分更新
（変わった行は UPDATE、増えた行は INSERT、減った行は DELETE）にする。
