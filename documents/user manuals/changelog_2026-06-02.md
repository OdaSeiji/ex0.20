# 変更ログ（2026-06-02）

## 1. die_inspection.html — 入力チェック・UX改善

**内容**
- 測定日が未入力のまま保存しようとするとアラートを表示
- 新規入力時、測定日のデフォルト値を今日の日付に設定
- 添付ファイルのサムネイルクリックで拡大モーダル表示
  - モーダル内画像をクリックするとさらに拡大（180vw）、スクロール可能
  - 再クリックで元のサイズに戻る
  - ×ボタン・背景クリック・ESCキーでモーダルを閉じる

---

## 2. die_progress_list.html — 画像拡大機能追加

**内容**
- プレビュー画面の添付ファイルサムネイルクリックで拡大モーダル表示
  - die_inspection.html と同じ動作（ズームトグル・ESCキー対応）

---

## 3. die_diagnosis.html — 入力チェック・UX改善

**内容**
- 診断日が未入力のまま保存しようとするとアラートを表示
- 新規入力時、診断日のデフォルト値を今日の日付に設定
- 測定情報（読み取り専用）・診断添付ファイル両エリアで画像拡大対応
  - die_inspection.html と同じ動作（ズームトグル・ESCキー対応）

---

## 4. die_import.html — 編集・削除機能追加

**対象ファイル**
- `die_import.html`
- `php/die_import/update_import_row.php`
- `php/die_import/delete_import_row.php`（新規）

**内容**
- 編集パネルに `die_number`（型番）と `budget_id` の入力欄を追加
  - 型番は必須チェックあり、大文字に自動変換
  - 保存時に `import_error` を自動クリア
- 各行に「削除」ボタン（赤）を追加
  - 転送済みの行は削除不可（ボタン無効）
  - `import_flag = 0` の行のみ DB から削除
- CSV ヒントテキストの「C列 = 品番」を「C列 = production_number」に修正
- `isPending` の判定を `import_flag != 1` に変更（NULL値も未転送として扱う）
