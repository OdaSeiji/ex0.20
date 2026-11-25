# 2026-11-15 金型の3つのテーブルの整理（m_dies・t_die_handover・t_die_handover_progress）

目標：3つのテーブルに分かれて重なっている金型の情報を整理し、1か所にまとめる。
（引き継ぎファイル 2026-10-25 の 4章 課題 20「似たテーブル」の続き。2026-11-08 に Windows でまとめた記録は push 前に消えたので、ここに書き直す）

数字は harbor のローカル DB（本番をぼかした写し、11/06 の直しの前の状態）で数えたもの。本番とは少し違う。

---

## 1. テーブルの役割

| テーブル | 中身 | 主に使う画面 | 1金型あたり |
|---|---|---|---|
| `m_dies` | 金型本体の情報 | 金型管理 die_register.html | 1行（1,373型） |
| `t_die_handover` | **部品単位**の、発注・輸出・固定資産登録までの流れ | 金型輸入固定資産登録 handover_list.html | 本体の行＋部品の行（1,313型・1,388行、部品の行 155） |
| `t_die_handover_progress` | 金型本体の、到着から量産開始までの進捗 | 金型進捗一覧 die_handover_progress.html | 1行（1,295型） |

ユーザーの説明（11/15）：
- `m_dies` と `t_die_handover_progress` は型本体の記録。
- `t_die_handover` は部品単位なので、同じ金型に到着日などが複数ある。
- **固定資産登録日と金型移管日は同じ意味**。

## 2. 重なっている項目と、決まったこと

| 項目 | `m_dies` | `t_die_handover` | `t_die_handover_progress` | 状態 | 決まったこと |
|---|---|---|---|---|---|
| 金型移管日＝固定資産登録日 | — | `asset_registration_applied_at`（本体の行） | `die_handover_at` | 食い違い 0（handover_list で保存すると両方に書く）。進捗だけに日付 12型（11/06 の直し B の対象）→ **11/25 本番で直した（一致 821型、食い違い 0、進捗だけ 0）** | 同期済み。整理のときにどちらか1か所にする |
| **日本への提出日** | — | `submitted_to_japan_at`（画面名「日本送付日」） | `submit_dimensional_inspection_to_japan_at` | 一致 0、平均 156日ずれ。**進捗側はどの画面からも入力・表示されていない**（昔の移行データ） | **11/15 決定：正は `t_die_handover.submitted_to_japan_at`。`t_die_handover_progress.submit_dimensional_inspection_to_japan_at` は消す** |
| 到着日 | `arrival_at` | `die_arrived_at`（部品ごと） | `arrival_at` | 3か所とも食い違い（下の 3 章） | 未決（次の懸案） |
| 日本の検査日 | — | `inspection_passed_at`（画面名「検査合格日」） | `jp_dimensional_inspection_at`（画面名「日本型検合格日」） | 両方の画面で入力できる。230型でずれ（平均 2.5日、ほとんど 60日以内） | 未決 |
| 日本の検査番号 | — | `inspection_number`（画面名「検査書番号」） | `jp_dimensional_inspection_document_number` | 進捗側はどの画面にも無く、ほぼ空（両方入っているのは 1型） | 未決（日本への提出日と同じく、消す候補） |
| 金型番号 | `die_number` | `die_model_code`（文字） | — | 全部一致（空 217行） | 未決 |
| 品番 | `production_number_id` | `product_code`（文字） | — | 一致 866、違う 150、空 217（ぼかしの影響もあるかも） | 未決 |
| 備考 | `die_note` | `note2` | `memo` | 別々に書いている | 未決 |

### 日本への提出日の列を消す前に

- ローカルでは、`t_die_handover` 側が空で、進捗側にだけ日付がある金型が 23型ある。
  消す前に、この日付を `t_die_handover.submitted_to_japan_at` に移すか捨てるかを決める。
- 本番の件数は、読むだけの SQL で確かめてから。

## 3. 到着日（次の懸案）

ユーザーの説明（11/15）：
- `m_dies.arrival_at` と `t_die_handover_progress.arrival_at` は同じになる「はず」。
- 本体の到着日は、`t_die_handover.die_arrived_at`（部品ごと）のいちばん早い日になる「はず」。

実際（ローカル）：

| 比べたもの | 一致 | 違う |
|---|---|---|
| `m_dies` と 進捗 | 805 | 284（1〜7日 115、8〜60日 72、60日超 64） |
| `m_dies` と 部品の行の最早 | 636 | 487 |
| 進捗 と 部品の行の最早 | 717 | 371 |

到着日を書いている画面：
- die_register.html → `m_dies.arrival_at` だけ
- handover_list.html → `t_die_handover.die_arrived_at` だけ
- die_handover_progress.html → `t_die_handover_progress.arrival_at` だけ
- die_arrival.html → 3つ全部（今は止めてある画面）

それぞれの画面が自分のテーブルにしか書かないので、ずれていく作りになっている。

### 到着日が入っている金型の数（ローカル、全 1,373型。t_die_handover は本体の行だけ）

| テーブル | 到着日が入っている |
|---|---|
| `m_dies` | **1,148型（いちばん多い）** |
| `t_die_handover_progress` | 1,089型 |
| `t_die_handover`（本体の行） | 1,051型 |

### 到着日について決まったこと（11/15）

- **値は `m_dies` を正にする**（ほかに情報が無いので、いちばん多い `m_dies` の値を使う）。
  `m_dies` が空の金型だけ、ほかの記録で補う（ローカルで 45型。`t_die_handover` と進捗で日付が違う 23型は、早いほう＝進捗の日）。
- **置き場所は `t_die_handover`**（発注伝票＝部品ごとに、発注・輸出・到着・固定資産登録の流れを持つテーブル）。
  - 金型本体の到着日 ＝ 本体の行の到着日。`m_dies` の値で上書きする（ローカルで 413型が変わる）。上書きの前に、テーブルを丸ごと写して残す。
  - 部品の行の到着日は、ほかに無いデータなので、そのまま残す。
  - いずれ `t_die_handover` に代わる「発注伝票ごとのテーブル」を作るときも、到着日はそこに置く。
  - `m_dies.arrival_at` と `t_die_handover_progress.arrival_at` は、いずれ消して、本体の行を読むようにする。

### 先に本番で確かめること

`documents/2026-11-15_die_handover_rows_check.sql`（読むだけ）を本番で実行する。
- A（1〜3）：`t_die_handover` に1行も無い金型（ローカル 60型）。ローカルでは全部が `-ZZZ`・`-20K` などの複製の金型で、元の金型は `t_die_handover` にある。
  複製の金型（1つの金型で複数の長さ、引き継ぎ 8-2）なら、到着日は元の金型のものを見ればよく、行を作らなくてよい。
- B（4〜5）：部品の行しかない金型（ローカル 80型）。11/06 の直し A で、本番では 0 に近いはず。

## 4. 1対0 の金型（`t_die_handover` に行が無い金型）について決まったこと（11/24）

`m_dies` にあるのに、`t_die_handover` に `die_id` が1行も無い金型（1対多のはずが 1対0）。
ローカルで 60型。登録日は 2023-12〜2026-09-18（2025年以降が 51型）で、新しいものは全部 `-20K` などの複製の金型。今も増えている。

金型を `m_dies` に登録できるページは2つだけ：

| ページ | メニュー | 登録のしかた | `t_die_handover` | `t_die_handover_progress` |
|---|---|---|---|---|
| `die_import.html` | 新規金型登録（CSV一括） | `php/die_import/transfer_to_dies.php` | 本体の行を作る | 行を作る |
| `die_register.html` | 金型管理（手で1型ずつ） | `php/die_register/InsDieV2.php` | **作らない** → 1対0 になる | 作らない |

決まったこと：
1. **`die_register.html` の新規登録（`InsDieV2.php`）で、`t_die_handover` の本体の行（`is_accessory_item_flag = 0`）も作る。** いつやるかは別として、必ずやる（到着日を `t_die_handover` に移すときには必要）。
   → **11/24 実装（未 push）**：`InsDieV2.php` が `m_dies` と本体の行（`is_accessory_item_flag = 0`、到着日も入れる）を同じトランザクションで作る。到着日が空だと `''` で日付エラーになり登録できなかったのも直した（NULL にする）。
   あわせて `DelDie.php`：外部キーが RESTRICT なので、中身が空の本体の行（登録のときに作った行）だけ一緒に消す。発注・検査・固定資産などが入っている金型は今までどおり消せない。
2. **`t_die_handover_progress` は今のままでよい。** 到着日はもう記録していない（画面は `t_die_handover` から読み、保存もしない。列だけ残っている）。
   `die_id` に UNIQUE（`uk_die_id`）があるので2行以上にはならない。行が無い金型（ローカル 78型）は、画面が `LEFT JOIN` で表示し、最初に入力したときに `save.php` が行を作る。
3. **すでに 1対0 の金型に本体の行を作る SQL は、本番の確認 SQL（`2026-11-15_die_handover_rows_check.sql`）の結果を見てから作る。** 到着日を移す SQL と一緒に作る予定。

## 5. 切替業連 No. を品番に移す（11/24 決定）

今：`t_die_handover_progress.production_site_change_notice`（varchar(100)、`vol.125` のような番号）。型検進捗管理（die_handover_progress.html）だけが読み書き。
ユーザー（11/24）：切替業連は**品番ごと**に発行される（型ごと・部品ごとではない）。**1つの品番に2回出ることは無い。**

ローカル：値がある品番 501。全部の金型に同じ値 158、1つの金型にだけ値 342、**金型で値が違う 1**（PN0683 / DM245：V01D vol.616、V01E vol.518）。

決まったこと：
1. `m_production_numbers` に列を1つ足して移す（品番ごとに金型の値を写す。食い違う品番は自動で写さず、どれが正しいか決めてから）。
   進捗の今の列はしばらく残して、あとで消す。
2. 画面：表示は金型の品番から読む。保存は `m_production_numbers` に書く（同じ品番の金型すべてに反映）。
3. **ロックは続ける**：切替業連 No. が入っている品番の金型はチェックボックスを押せない（クリックで外せるのは今まで通り）。
4. 本番の順番：SQL → git pull。
5. 画面の名前は「切替業連発行日」→「**切替業連 No.**」（vi：「Số VOL」）。**11/24 変更済み（未 push）**。日付の列は持たない。

11/25 本番の確認：値がある品番 522、**金型で値が違う品番 0**（②〜④ も何も出なかった）→ 決めることは無し。

**11/25 実装**：
- SQL：`documents/2026-11-25_change_notice_to_pn.sql`（`m_production_numbers.change_notice_no` を足して写す。値の違う品番は写さない。ローカル 500品番、DM245 は写さず）。進捗の古い列は残す。
- `get_list.php`・`get_by_die.php`：`pn.change_notice_no` を今までと同じ名前（`production_site_change_notice`）で返す → 画面の表示・ロック・CSV はそのまま。
- `save.php`：切替業連 No. は進捗の表に書かず、金型の品番に書く。編集画面に同じ品番の金型が2つ以上あると、変えていない方も古い値を送るので、**保存する前の値と違うものだけ**書く（変えた方が勝つ）。品番の無い金型は保存しない。
- 画面：編集画面で1つの金型の切替業連 No. を変えると、同じ品番の金型の欄も同じ値になる。欄にマウスを乗せると「品番ごと」の説明。
- ロック：品番に値があると、その品番の金型は**全部**チェックボックスが押せなくなる（今までは値を入れた金型だけ）。あとから届いた同じ品番の金型も、パスワード（今まで通り）で外す。
- 本番の順番：SQL → git pull（memo.md 11/25 の TODO）。
- **11/25 本番で実行済み**：522品番に写した（写せなかった品番 0）。CP9632T-V04B の行 1091 を本体の行にした（部品の行だけの金型 0）。
- CP9632T-V04B（部品の行だけの金型）：`documents/2026-11-25_cp9632t_v04b_body_row.sql` で行 1091 を本体の行に。
