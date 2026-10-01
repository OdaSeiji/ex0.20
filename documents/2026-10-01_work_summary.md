# 2026-10-01 作業記録（まとめ）

## 1. （テスト）サンプル位置の図を現場の名前で表示

サンプル名の付け方を現場で変えるのは難しいため、頭 T・尾 Z の案をやめ、現場のルールで表示するように戻した（`sample_map_test.html`）。

| nBn | サンプルの名前 |
|---|---|
| 1B1〜1B4 | H、E |
| 2B1 | H、A・B、E |
| 3B1 | H、A・B、C・D、E |
| 4B1 | H、A・B、C・D、E・F、END |
| 5B1 | H、A・B、C・D、E・F、I・K、END |

- 中央は M、M1、M2 …（記録どおり）。中央 M の位置は現場でも決まっていない（H と E の間であることは確か）。
- 名前の読み替えをやめ、過去のエッチング・表面粗さの記録と直接比較する。1B1・2B1・3B1 の押出で記録と完全一致。
- DB に呼び名を持ち込まない方針は変えない（新しいテーブルでは位置の意味で持ち、表示のときに名前を組み立てる）。
- 現場ではまだ使っていない。使い始めて意見が出たら、表示を見直す。

## 2. 押出実績の入力元・入力日時

詳細：`documents/2026-10-01_press_entry_source.md`

- `t_press.entry_source`（0＝旧画面 ex0.11、1＝ex0.20 押出日報）を追加し、押出日報の新規保存で 1 を入れる。
- `t_press.inserted_at`（ユーザーが追加、INSERT 時の日時が自動で入る）を NULL 可にし、列を追加した時刻のままだった過去分 12,307件を NULL にした。
- 押出日報の実績一覧に「入力元」「入力日時（mm-dd hh:mm）」を表示（入力漏れチェック用、memo.md 20261001 の要望）。
- DB 変更はローカル・本番とも実行済み。

## 3. 金型の到着日を引き継ぎ一覧に一本化

### 経緯
到着日が、次の3か所に別々に入っていた（もとは別々の担当者が管理していた Excel をデータベース化したことによる）。

| 場所 | 入力していた画面 |
|---|---|
| `t_die_handover.die_arrived_at` | 引き継ぎ一覧（handover_list.html）。**今も担当者が更新している** |
| `t_die_handover_progress.arrival_at` | 金型引き継ぎ進捗（die_handover_progress.html） |
| `m_dies.arrival_at` | 金型 到着日入力（die_arrival.html）が3か所すべてを同時に書き換えていた |

`t_die_handover` の方がレコード（1,290 対 1,195）も到着日（1,173 対 1,089）も多く、新しい。
両方に到着日がある型のうち、約3分の1で日付が食い違っていた（最初に入力した行で比べて 1,088件中371件）。

### 決めたこと（ユーザー決定）
- 到着日は **`t_die_handover.die_arrived_at`（引き継ぎ一覧）を正** とする。
- 同じ型で複数行あるとき（型＋部品）は、**最も過去に入力した行** を正とする（`MIN(id)`）。2行目以降はすべて部品の行だった。
- `t_die_handover_progress.arrival_at` は使わないが、列と値は残す。

### 変更
| ページ・ファイル | 変更 |
|---|---|
| 金型引き継ぎ進捗（`die_handover_progress.html`、`php/die_handover_progress_php/get_list.php`） | 到着日を引き継ぎ一覧の最初の行から表示。一覧のセルも「選択を編集」の画面も表示だけ（編集不可） |
| `php/die_handover_progress_php/save.php` | 到着日を保存の対象から外した |
| 金型進捗確認表（`die_issue_progress.html`、`php/die_handover_progress_php/get_by_die.php`） | 同じく引き継ぎ一覧の到着日を表示 |
| 金型 到着日入力（`die_arrival.html`） | 停止。「選択行に適用」「保存」ボタンを常に不活性にし、停止中のお知らせ（引き継ぎ一覧で入力）を表示 |
| `php/die_arrival/save_arrival.php` | 停止（HTTP 410 を返し、書き込まない）。再開するときは停止を外し、die_arrival.html の `PAGE_DISABLED` を false にする |
| 金型立上実績（`die_startup_report.html`） | 変更なし。すでに `t_die_handover.die_arrived_at` を使い、部品の行を除いて数えていた |

### ⚠️ ローカル DB の誤更新（要復旧）
`save_arrival.php` を止める途中で、確認のつもりで送ったテストの保存が実行され、ローカル DB の次の値が 2026-10-01 に書き換わった（本番には影響なし）。

| テーブル | 行 |
|---|---|
| `m_dies.arrival_at` | id 1（CQ63T-V01C） |
| `t_die_handover.die_arrived_at` | id 59（die_id 1） |

ローカルはバイナリログを取っていないため、本番の値で戻す。本番で次を確認して、その値をローカルに入れる。
```sql
SELECT id, arrival_at FROM m_dies WHERE id = 1;
SELECT id, die_arrived_at FROM t_die_handover WHERE die_id = 1;
```

## 4. 宿題

- **似たような表が2つある問題を、今後直す。** `t_die_handover`（引き継ぎ一覧）と `t_die_handover_progress`（引き継ぎ進捗）は、どちらも型ごとの引き継ぎを扱い、到着日のような同じ項目を別々に持っている。到着日は表示を一本化したが、表そのものの整理（1つにまとめる、役割を分ける など）は未対応。`m_dies.arrival_at` も含めて整理する。
- `t_die_handover_progress.arrival_at` と `m_dies.arrival_at` の削除は、整理が終わってから。
- 押出指示書（press_directive.html）を ex0.11 と同じレイアウトにしたい要望（memo.md 20261001）：項目はほぼ同じなので、使っている人に問題点を確認中。

## 5. 更新履歴（whats_new.html）

次の5件を追加した（日本語・ベトナム語）。
- 2026-10-01 押出日報の実績一覧に入力元・入力日時を表示
- 2026-10-01 金型の到着日を引き継ぎ一覧に一本化
- 2026-10-01 （テスト）サンプル位置の図を現場の名前で表示
- 2026-09-28 トップメニューの設備稼働状況に3号機を追加
- 2026-09-28 品質評価入力の工程名「時効」を「硬度」に変更
