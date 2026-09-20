# press_directive.html 再構築 フェーズ1完了(ex0.11 UX移植)

- 作成日: 2026-09-20
- 対象システム: ex0.20 (`C:\xampp\htdocs\ex0.20`)
- ステータス: **フェーズ1完了・動作確認済み。フェーズ2(PDF帳票印刷)は未着手。**

## 1. 背景

`press_directive.html`を、ユーザーが今も使っている旧ex0.11版(`C:\xampp\htdocs\diereport\ext0.11\exd11\MakingPressDirectiveV11.html` + `scr/MakingPressDirectiveV12.js`)のUXに近づける形で全面再構築した。「大胆に変更したい」という方針のもと、単なるバグ修正(`get_variants.php`の削除済み列参照)に留まらず、ex0.11の操作感を移植した。

移植前の状態は`documents/die_production_number/2026-09-20_variant_id_columns_added.md`4節を参照。

## 2. ex0.11から移植した機能(フェーズ1)

1. **前回値表示**: 履歴行をクリックして編集モードに入ると、各入力欄の上に読み込んだレコードの値を薄く表示(`前回: xxx`)
2. **Enterキーでの自動遷移**: 全22項目を順番にEnterキーだけで移動できる(`fieldOrder`配列で管理)
3. **入力完了の色分け**: 未入力・範囲外は黄色背景、条件を満たすと白背景に変化。全項目完了するまでSave/Updateボタンをdisabled(ex0.11の`no-input`/`complete-input`と同じ考え方、各項目のバリデーション範囲もex0.11の値をそのまま踏襲)
4. **押出長さのリアルタイム自動計算**: ビレットサイズ・長さ・放電しろ・選択中品番の比重(`specific_weight`)・金型の穴数(`hole`)から`calPressLength()`と同じ式で計算し、画面上部に大きく表示。ex0.20にはこれまで無かった機能
5. **履歴行クリックでの編集/削除**: 行クリックで編集モードへ読込み、選択中の行をもう一度クリックすると削除確認ダイアログを表示→削除(ex0.20側にこれまで削除機能自体が無かった)
6. **保存直後に全項目クリアして日付欄にフォーカス**(連続入力しやすくする、ex0.11と同じ)

**今回スコープ外(フェーズ2として保留、ユーザー了承済み):** PDF帳票印刷("Phiếu thông tin sản xuất")。`php/MakingPressDirective/SelForPrintPageV6.php`が`m_pressing_type`・`m_bolster`・`m_nbn`・`m_dies_diamater`・`m_production_numbers_sub`・`t_press_plan`・`t_press_work_length_quantity`など約10テーブルを結合し、押出長さもリアルタイム表示用とは別の式(`cross_section_area`ベース)を使っている。着手時はこの式の統一方針を先に決める必要がある。

## 3. 金型⇔品番の多対多モデルへの対応(データ設計面の変更)

旧`press_directive.html`は`m_dies.production_number_id`という単一FKのみを前提にしていたが、新モデル(`m_die_production_number_variants`、[[project_die_pn_1tomany]]参照)では1つの金型が複数の品番に紐づける。Step1に以下を追加:

- 金型選択後、`get_die_products.php?die_id=X`でその金型に紐づく品番一覧を取得
- 0件: 警告表示(「die_production_number.htmlで先に登録してください」)、保存は続行可能(`die_production_number_variant_id`はNULLのまま)
- 1件: 自動選択
- 2件以上: ラジオボタンで選択を要求(2026-09-20時点で該当7金型: die_id 116,192,193,731,796,797,1143)

選択された品番(`variant_id` = `m_die_production_number_variants.id`)が`t_press_directive.die_production_number_variant_id`として保存される。

## 4. 変更したPHPファイル

- `php/press_directive/get_dies.php`: `m_dies_diamater`をJOINし`hole`・`die_diamater`を追加で返すよう変更。旧`production_number_id`フィルタ分岐(未使用だった)は削除
- `php/press_directive/get_die_products.php`(新設): `die_id`から`m_die_production_number_variants`経由で紐づく品番一覧(`variant_id`・`production_number`・`specific_weight`・`production_length`)を返す
- `php/press_directive/get_variants.php`(**削除**): 9/19の簡素化で削除済みの列(`production_number`,`length`,`is_default`)を参照する不整合コードだったため。`get_die_products.php`に置き換え
- `php/press_directive/get_directive_history.php`: `m_die_production_number_variants`・`m_production_numbers`をLEFT JOINし、品番・長さを履歴表示できるよう変更
- `php/press_directive/delete_directive.php`(新設): 履歴からの削除用
- `php/press_directive/get_ordersheets.php`(**削除**): `ordersheet_id`をt_press_directiveに追加しない方針が確定し、この画面の受注番号選択UI自体を撤去したため完全に不要になった
- `php/press_directive/press_directive_common.php`: `directiveColumns()`/`bindDirectiveValues()`に`die_production_number_variant_id`を追加

## 5. 動作確認(2026-09-20、ローカル環境、Chrome操作)

- 型番`CQ12T3L-V01B`(複数品番紐づけ金型の1つ)で検索→選択→2件の品番から`C2Q12A-AD367-20K`をラジオ選択→サマリーに`Φ260mm`・品番・比重`1.01 kg/m`表示を確認
- ビレット長さ`1200`・放電しろ`35`入力→押出長さ`127.3m`がリアルタイム表示されることを確認
- 履歴行(2025-09-23、ID 11131)クリック→全項目に前回値表示付きで読込み、押出長さも再計算(62.7m)されることを確認
- 同じ行を再クリック→削除確認ダイアログ表示→キャンセルで実データを保護
- 新規指示(テストデータ、備考に"TEST ENTRY"と明記)を全項目入力→保存ボタンが有効化されることを確認→保存→履歴に反映され品番列も正しく表示されることを確認→編集読込み→削除確認→削除、までテスト完了。テストデータはDBに残っていない

## 6. 残タスク

- フェーズ2: PDF帳票印刷(`SelForPrintPageV6.php`相当)。着手前に押出長さ計算式の統一方針と、必要な追加テーブル(`m_bolster`等)がex0.20の運用にそのまま使えるかの確認が必要
- 本番PCへの反映: 今回の変更は**ローカルのファイル変更のみ**(DBスキーマは9/20の別作業で本番適用済み[[project_die_pn_1tomany]])。`press_directive.html`・関連PHPのデプロイ(git push等)は別途必要
