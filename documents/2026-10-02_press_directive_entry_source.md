# 2026-10-02 押出指示書の入力元（ex0.20 プレス指示作成か旧画面か）を記録

## 目的

押出実績（`t_press.entry_source`、2026-10-01）と同じく、押出指示書（`t_press_directive`）が
ex0.20 のプレス指示作成（press_directive.html）で作られたものか、旧画面（ex0.11 の MakingPressDirective）で作られたものかを区別できるようにする。

記録するのは **作った画面だけ**。あとから別の画面で直しても、値は変えない。

## 内容

- `t_press_directive.entry_source`（入力元）を追加
  - `0` ＝ 旧画面（ex0.11）。列の初期値なので、ex0.11 は何も変えなくても 0 が入る（ex0.11 の InsData*.php は列名を指定して INSERT している）
  - `1` ＝ ex0.20 プレス指示作成
- ex0.20 の新規保存（`php/press_directive/save_directive.php`）で `entry_source = 1` を入れる。画面の「複製」も新規保存なので 1 になる
- 更新（`update_directive.php`）では変えない（この列は更新の対象に含まれていない）
- 既存の押出指示書（14,894件）はすべて 0

## DB変更

| SQL | ローカル | 本番 |
|---|---|---|
| `t_press_directive.entry_source` の追加 | 実行済み | 実行済み（2026-10-02） |

**コードの反映より先に実行すること**（無いとプレス指示作成の新規保存がエラーになる）。

```sql
ALTER TABLE t_press_directive
  ADD COLUMN entry_source TINYINT NOT NULL DEFAULT 0
  COMMENT '入力元 0=旧画面(ex0.11) 1=ex0.20 プレス指示作成'
  AFTER created_at;
```

確認用：
```sql
SHOW COLUMNS FROM t_press_directive WHERE Field = 'entry_source';
SELECT entry_source, COUNT(*) FROM t_press_directive GROUP BY entry_source;
```

※ コメントに日本語を含むため、mysql コマンドラインでは UTF-8 の SQL ファイルを
`mysql --default-character-set=utf8mb4 ... < file.sql` で流す（phpMyAdmin ならそのまま貼り付けてよい）。

## 確認したこと

既存の押出指示書の内容をもとに、ローカルで保存 API（save_directive.php）からテスト用の押出指示書を1件保存し、
`entry_source = 1` が入ることを確認。テスト用の押出指示書は削除済み。

## 対象ファイル

- `php/press_directive/save_directive.php`
