# 2026-10-05 押出時間の標準（号機ごと）

## 目的

押出計画（press_plan.html）で、1日のスケジュール表（何時から押し、どれくらいかかるか）を出すため（現場の要望）。
押出の時間は次の考え方で計算する（ユーザー説明、2026-10-05）。

```
押出の時間 ＝ 金型交換直後の、自動運転までの時間（手動）
           ＋ ビレット本数 ×（ビレットの長さ ÷ ラム速度 ＋ ビレットを交換する時間）
```

- 自動運転までの時間・ビレットの交換時間は、**号機ごと**に画面で決める（1号機と3号機が同じでも、それぞれに入れる）。単位は秒
- ラム速度（mm/秒）とビレットの長さは、その型のいちばん新しい押出指示書の値
- 押出指示書の無い型は、号機ごとの「1本あたりの平均」（直近1年の実績）を使い、画面に「平均値を使っています」と出す
- 1日の始まりは 8:00（ほとんど HC のため）。休憩は考えない

## 画面

- `press_time_standard.html`（トップメニュー「各種設定」の5段目「押出時間の標準」）
- 号機ごとに2つの時間（秒）を入れて保存。更新する人（m_staff）を選ぶ
- 参考として、直近1年の実績からの推定（押出全体の時間 −「本数 × 長さ ÷ ラム速度」を、自動運転までの時間 ＋ 本数 × ビレット交換に当てはめた値）と、1本あたりの平均を横に出す。「推定値を入れる」ボタンで入力欄に写せる

2026-10-05 時点の推定（ローカル、直近1年）：

| 号機 | 自動運転まで | ビレット交換 | 1本あたりの平均 |
|---|---|---|---|
| 1 | 883 秒 | 29 秒 | 390 秒 |
| 2 | 845 秒 | 85 秒 | 552 秒 |
| 3 | 867 秒 | 65 秒 | 524 秒 |

## DB変更

| SQL | ローカル | 本番 |
|---|---|---|
| `m_press_time_standard` の作成と、号機 1〜4 の行の追加 | 実行済み（2026-10-05） | 実行済み（2026-10-05） |

※ 本番では最初 `CREATE TABLE` だけが実行され、号機の行が無いため画面に号機が出なかった。`INSERT` を実行して解決（2026-10-05）。

SQL は `2026-10-05_press_time_standard.sql`（日本語のコメントを含むため UTF-8。mysql コマンドなら `--default-character-set=utf8mb4`、phpMyAdmin ならそのまま貼り付け）。

```sql
CREATE TABLE m_press_time_standard (
  press_machine     TINYINT  NOT NULL COMMENT '号機（1〜4）',
  startup_sec       INT      NULL     COMMENT '金型交換直後の、自動運転までの時間（秒）',
  billet_change_sec INT      NULL     COMMENT 'ビレットを交換する時間（秒）',
  updated_at        DATETIME NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
  updated_staff_id  INT      NULL     COMMENT '更新した人（m_staff.id）',
  PRIMARY KEY (press_machine),
  CONSTRAINT fk_press_time_standard_staff FOREIGN KEY (updated_staff_id) REFERENCES m_staff (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='押出時間の標準（号機ごと）';

INSERT INTO m_press_time_standard (press_machine) VALUES (1), (2), (3), (4);
```

確認用：
```sql
SELECT * FROM m_press_time_standard;   -- 4行、値は NULL
```

## 対象ファイル

- `press_time_standard.html`、`php/press_time_standard/get.php`・`save.php`
- `index.html`（各種設定にカードを追加）
- `press_schedule.html`、`php/press_plan/get_schedule.php`・`save_schedule_day.php`、`press_plan.html`（スケジュール表のボタン）

## スケジュール表（press_schedule.html）

押出計画の右の一覧の「スケジュール表」ボタンで開く（選んだ計画の日・号機。選んでいなければ期間の始まりと号機の絞り込み）。
現場の計画表（ベトナム語の Excel、例：02/10/2026 MÁY 3）と同じ形。印刷できる（号機ごとに1ページ）。

- 列：金型（KHUÔN CHÍNH）、予備の金型（KHUÔN DỰ BỊ）、ビレットの長さ・計画・実績、押出材の計画・実績、計画の時間（分）、計画の開始・終了、実績の時刻、備考
- 並びは計画の「順番」。「5.1」のような小数の行は、整数部が同じメインの行の予備（t_press_plan に本数 0 の行で入っている）
- 押出材（計画）＝ ビレット本数 ÷ n × m × 穴数（nBn は最新の押出指示書）
- 実績は、その日・その号機・その型（予備を含む）の押出（t_press）と、ラックの本数（t_using_aging_rack）
- 押出指示書の無い型は「＊」を付け、1本あたりの平均で計算したことを表示
- 標準時間が未設定の号機は、実績からの推定で計算し、画面にそう表示する（印刷には出さない）

10/2・3号機で、現場の計画表（手で入れた時間）と比べた：CAA0T 100分→91分、NCQ38T2 30→38、CYR25SR 30→34、CP9650T 60→58、CYR10SR 120→107、CA50T 100→97。

## 開始の時刻と昼休み（日付・号機ごと）

現場の Excel（Downloads/PRESS PLAN.xlsx）を見ると、開始は 3号機で 8:00 と 8:30 が半々、昼休み（NGHỈ TRƯA）は いつも45分で、
3号機は39日中28日、1号機は35日中5日。昼休みは型の区切りに入れていて、時刻は 11:20〜12:20 にばらつく。
→ スケジュール表の画面で、日付・号機ごとに **開始の時刻** と **昼休みの有無** を決めて保存する（ユーザー了承）。
昼休みの位置は最初「12:00 にいちばん近い型の区切り」、「○○のあと」で選び直せる。
10/2・3号機を 8:30 開始・昼休みありにすると、昼休みは自動で 11:40〜12:25 になり、Excel と同じになった。

| SQL | ローカル | 本番 |
|---|---|---|
| `t_press_schedule_day` の作成 | 実行済み（2026-10-05） | 実行済み（2026-10-05） |

SQL は `2026-10-05_press_schedule_day.sql`。

```sql
CREATE TABLE t_press_schedule_day (
  plan_date           DATE     NOT NULL COMMENT '押出日',
  press_machine       TINYINT  NOT NULL COMMENT '号機（1〜4）',
  start_time          TIME     NULL     COMMENT '開始の時刻（NULL は 8:00）',
  has_lunch           TINYINT  NOT NULL DEFAULT 0 COMMENT '昼休み 0=なし 1=あり（45分）',
  lunch_after_plan_id INT      NULL     COMMENT '昼休みを入れる位置（この計画 t_press_plan.id のあと）。NULL は 12:00 にいちばん近い型の区切り',
  updated_at          DATETIME NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
  PRIMARY KEY (plan_date, press_machine)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='押出スケジュール表の日付・号機ごとの設定';
```

## そのほか Excel から分かったこと（未対応）

- 3号機は長さの欄に「1120 (VN)」「1200 (DUBAI)」「BILLET NGẮN」と、ビレットの種類を書いている（計画の段階で VN/Dubai を決めている）
- 1号機は本数を 600mm／1200mm に分け、重さ（トン）＝（600mm の本数 ÷ 2 ＋ 1200mm の本数）× 0.1332 を出している
- 備考に「TĂNG CA」（残業）

## 押出計画にビレットの種類と長さ（2026-10-05）

現場の Excel で「1120 (VN)」のように計画の段階でビレットの種類と長さを書いているため、`t_press_plan` に列を足した（ユーザー了承）。

- `billet_origin`：0=未定 1=Dubai 2=VN（最初は未定）
- `billet_length`：mm。空なら押出指示書の長さを使う（スケジュールの時間の計算にも、計画の長さを優先して使う）
  - **同日、現場の要望で長さの入力欄は削除**（列は残す。入力済みの長さはそのまま使う）
- 押出計画の中央の表に「ビレット」の欄（種類と長さ）、右の一覧とスケジュール表は「1120 (VN)」の形で表示
- ex0.11 の押出計画（InsPressPlanV9.php）は列名を指定して INSERT しているので、列を足しても動く（新しい列は 0 / NULL）
- ビレットの在庫と発注の目安の表（Dubai のみ）を VN に対応させるのは、このあと

| SQL | ローカル | 本番 |
|---|---|---|
| `t_press_plan` に `billet_origin`・`billet_length` を追加 | 実行済み（2026-10-05） | 実行済み（2026-10-05） |

SQL は `2026-10-05_press_plan_billet.sql`。

```sql
ALTER TABLE t_press_plan
  ADD COLUMN billet_origin TINYINT NOT NULL DEFAULT 0 COMMENT 'ビレットの種類 0=未定 1=Dubai 2=VN' AFTER quantity,
  ADD COLUMN billet_length INT     NULL              COMMENT 'ビレットの長さ(mm)。NULL は押出指示書の長さを使う' AFTER billet_origin;
```

確認用：
```sql
SHOW COLUMNS FROM t_press_plan WHERE Field IN ('billet_origin', 'billet_length');
```
