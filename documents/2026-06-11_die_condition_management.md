# 金型コンディション管理機能 追加作業記録
**日付：** 2026-06-11  
**バージョン：** V6.9  
**コミット：** `eb165c1`

---

## 背景・課題

`die_progress_list.html` の既存フローは「検査 → 診断 → 承認 → 修理計画 → 修理報告」という修理ループになっており、問題がある前提で永遠に回り続ける設計だった。

実際の運用では、診断で「修理不要」と判断されたとき、次のステップ（**量産試押**）へ進む必要があるが、そのための分岐・管理の仕組みがなかった。

---

## 設計方針

### フェーズ定義

| ID | 名称 | 意味 |
|----|------|------|
| 1 | Trial | 新規金型の立上、または量産中に問題が発生して修理に入った金型 |
| 2 | Mass Production Trial | 修理・立上が完了し、生産数を増やして品質の再現性を確認する工程 |
| 3 | Mass Production | 量産試押で品質が確認され、正式に量産移行した状態 |

- 既存の金型（1155件）は `die_condition_id = NULL` のまま据え置き
- `NULL` は「フェーズ管理対象外（従来の量産金型）」を意味する

### フェーズ遷移ルール

診断承認時に `ng_action` に基づいて自動判定：

```
ng_action = 2（修理）or 3（修理＋条件変更）
  → die_condition_id = 1（Trial）に設定

ng_action = 1（様子を見る）or 4（条件変更のみ） + die_condition_id = 1
  → die_condition_id = 2（Mass Production Trial）に昇格

ng_action = 1 or 4 + die_condition_id = 2
  → die_condition_id = 3（Mass Production）に昇格

die_condition_id = NULL or 3
  → 変更なし
```

---

## DBの変更

### マスターデータ投入

```sql
INSERT INTO m_die_conditions (id, name) VALUES
(1, 'Trial'),
(2, 'Mass Production Trial'),
(3, 'Mass Production');
```

### 関連テーブル（既存・活用）

| テーブル | 役割 |
|---------|------|
| `m_die_conditions` | フェーズ名マスター（今回データ投入） |
| `m_dies.die_condition_id` | 金型の現在フェーズ（FK） |
| `t_die_condition_history` | フェーズ変更履歴の記録 |

---

## 修正ファイル

### `php/die_progress_php/save_diagnosis.php`
- `ng_action` が 2 または 3 のとき `need_fix = 1` を自動計算するように修正
- INSERT / UPDATE の両方に `need_fix` を追加（従来は常に `0` のままだったバグを修正）

### `php/die_progress_php/approve_diagnosis.php`
- `ng_action` を取得して `need_fix` を補正（旧データ互換対応）
- `die_condition_id` を取得するよう SELECT を拡張
- **Step 6 追加：** `need_fix = 1` のとき `m_dies.die_condition_id = 1` を UPDATE
- **Step 7 追加：** `need_fix = 0` かつ `die_condition_id ∈ {1, 2}` のとき次フェーズへ昇格
- いずれの変更も `t_die_condition_history` に履歴記録

### `php/die_progress_php/get_progress_list.php`
- `m_die_conditions` を LEFT JOIN して `die_condition_id` と `die_condition_name` を返すよう追加

### `die_progress_list.html`
- コンディションバッジ用 CSS 追加
  - Trial：オレンジ（`#f57c00`）
  - Mass Production Trial：青（`#1565c0`）
  - Mass Production：緑（`#2e7d32`）
- 「コンディション」列をテーブルに追加（押出種別の右隣）
- 日本語 / ベトナム語辞書にキー追加

---

## 今後の課題

- コンディションでのフィルタリングボタン追加
- 金型台帳（die_watch 等）でのコンディション表示
- 量産試押の結果を記録するテーブル・画面（`t_die_mass_trial` 等）の新設
