# press_directive.html 印刷出力(フェーズ2)完了

- 作成日: 2026-09-20
- 対象システム: ex0.20 (`C:\xampp\htdocs\ex0.20`)
- ステータス: **実装・動作確認済み(JS経由でのテンプレート生成検証)。**

## 1. 背景

`documents/die_production_number/2026-09-20_press_directive_rebuild_phase1.md`でスコープ外としていたPDF帳票印刷機能(フェーズ2)に着手。ユーザー確認の結果、必要なテーブル・列(`m_bolster`・`m_nbn`・`m_dies_diamater`・`t_press_plan`・`t_press_work_length_quantity`・`m_production_numbers.cross_section_area`等)はすべて`extrusion`DBに既存(ex0.11と同一DBを共有しているため)と判明し、**ex0.11の帳票をほぼそのまま移植する方針(選択肢A)**で合意。

## 2. 実装内容

### 2.1 `php/press_directive/get_print_data.php`(新設)

ex0.11の`php/MakingPressDirective/SelForPrintPageV6.php`のクエリを移植。主な変更点:

- **品番の解決方法**: `LEFT JOIN m_die_production_number_variants v ON v.id = t_press_directive.die_production_number_variant_id` → `LEFT JOIN m_production_numbers pn ON pn.id = COALESCE(v.production_number_id, m_dies.production_number_id)`。新経路(die_production_number_variant_id、ex0.20で新規作成した指示)を優先し、未設定(旧ex0.11由来のレコード、または品番未紐づけの金型)の場合は旧経路(`m_dies.production_number_id`、既定品番)にフォールバックする。[[project_die_pn_1tomany]]で確立した「新旧経路並立」の設計方針を踏襲。
- `m_production_numbers_sub`(H/A/B/C/D/E/F/I/K/END列、切断寸法)の結合キーも同じ`pn.id`に統一
- それ以外のJOIN(`m_pressing_type`・`m_bolster`・`m_staff`・`m_nbn`・`m_dies_diamater`・`t_press_work_length_quantity`のサブクエリ・`t_press_plan`)は元クエリと同一

### 2.2 `press_directive_print.html`(新設、独立ファイル)

当初は`press_directive.html`内にJS文字列としてテンプレートを埋め込んでいたが、4節の調整作業を機に**独立したHTMLファイル**に分離した。`?id=<指示ID>`をURLパラメータで渡すだけでブラウザから直接開ける(例: `press_directive_print.html?id=9871`)。

- ページ読込み時に`get_print_data.php`からデータを取得し、ex0.11の印刷テンプレート(「PHIẾU THÔNG TIN SẢN XUẤT」帳票、`MakingPressDirectiveV12.js`の`#print__button_2`ハンドラ相当)をほぼそのまま移植した`page`文字列を`document.write()`で描画
- `?autoprint=1`を付けた場合のみ`window.print()`を自動実行(通常はプレビュー表示のみ、印刷は手動)
- 押出長さの計算(`calPrintPressLength()`)は、リアルタイム表示用の計算式(`calPressLength`、billet重量×container断面積ベース)と**同じ式**を使用(ex0.11のJS内`calPressLength()`もこの式を再利用しており、SQLの`ratio`/`work_speed`とは別の指標である比率・速度計算(`cross_section_area`ベース)とは独立している。当初「2つの矛盾する計算式がある」と懸念していたが、実際は「長さ」と「比率・速度」で別の指標を計算しているだけで、矛盾ではなかった)
- 帳票内の手書き記入欄(検査記録テーブル・ラック管理・時間記録テーブルなど)もex0.11と同じ形で保持(行数は4節の通り調整済み)

### 2.3 `press_directive.html`側

- ボタン行に「指示書を印刷」ボタンを追加。`editingId`(履歴から読み込んだレコード)がある時のみ活性化(`checkAllComplete()`内で制御)
- `printDirective()`は`window.open(`./press_directive_print.html?id=${editingId}`, "_blank")`を呼ぶだけの薄い関数に変更(テンプレート本体は2.2のファイルに分離)

## 3. 動作確認(2026-09-20、ローカル環境)

`window.open`を一時的にモック化し、実際に印刷ダイアログを開かずに生成されたHTML文字列を検証:

- 型番`APT2-V01B`の履歴(ID 9871)を読み込み→印刷データ取得→テンプレート生成(71,954文字)
- `undefined`・`NaN`・未展開の`${...}`・文字列`"null"`・`[object ...]`の混入なし
- 黄色ハイライト部分の値を確認: 押出長さ`25.9m`(card1のリアルタイム表示と一致)、押出比率`49`、押出条件(nBn×穴数)`1B1*2`
- 実際の`window.print()`(ブラウザ標準のPDF化・印刷ダイアログ)自体は、ユーザー環境への影響を避けるためこのセッションでは実行していない(コード自体はex0.11と同じ確立された方式)

## 4. レイアウト崩れの修正(2026-09-20、同日追加対応)

実際に印刷プレビューを目視確認したところ、ヘッダー部(型番・品番情報)とその下の3列レイアウトの境界で表がお互いに重なる不具合が見つかった。原因は、ヘッダー行に割り当てられていた高さ(`%`指定)が実際のコンテンツ量に対して不足しており、はみ出した分だけ下のレイアウトが早く始まってしまうことだった。

**対応方針の転換:** 印刷テンプレートを`press_directive.html`内のJS文字列から独立ファイル`press_directive_print.html`に分離した。`?id=<指示ID>`をURLに付けてブラウザで直接開けるため、ユーザーがブラウザの開発者ツールで数値を試行錯誤しながら、都度「この値に直して」「リロードして」と指示する形で調整を進めた(旧来のJS文字列内蔵方式だと、変更のたびに新規ウィンドウを開き直す必要があり非効率だったため)。

**最終調整値:**
- ヘッダー行(型番・品番・Billet情報のブロック): `height: 7%` → `105px`
- 3列レイアウト(Số bundle/Thông số đùn/Stt等): `height: 93%` → `1002px`、`margin-top: 5px` → `0`
- 「Ghi chú」欄: `height: 175px` → `140px`
- Sttテーブル(`makePrintDetailTable()`): 64行 → 62行
- 時間記録テーブル(`makePrintTimeTable2()`、Thời gian kéo/Thời gian cắtの2箇所で共通利用): 14行 → 12行

px換算の考え方: `body`の高さが`29.7cm`(A4、≒1122px@96dpi)に固定されているため、そこから各階層の`%`を辿って算出した理論値(75px/1002px)を出発点とし、実際のプレビューを見ながら微調整した(最終的にヘッダー行は105pxに着地)。

ユーザーによる最終確認: 「良いと思います。これで完成」(2026-09-20)。

## 5. 残タスク

- 本番への反映(git commit/push)は未実施
