# 2025/02/06

billet charge page 作成。ビレットストック情報。`press directive`からビレットサイズと、長さはデフォルト値として入力した方がいい。その為には、上部の表を読み込んだ際に、その二つの値も読み込んでおいて、表の中に仕込む。表示しない。表の改造をしないと。

![](./img/20250206-01.png)

デフォルト値をどうやって入れるか、悩む。

# 2025/02/12

どうやって、正しい値を入力したか？確認しやすくする。まずは、選択した PressDirective のマシン番号、インチサイズ、を大きく表示する。これは、完了。ビレットベンダー名を入れるのは、`select`要素。これは完成。一度、ビレットストックに入力を開始したら、他の金型は選べないようにする。または、選んだら、別の設備の、ビレットストックを表示するようにする。つまり、ビレットストックを管理する表が必要になる。

# 2025/02/13

最初に選ぶべきは、設備名。その後、金型を選ぶ。の流れではないか？それとは別にビレットストックのページを作る。
![](./img/20250213-01.png)
ここまでできた。次は、マシンナンバーによって、押出指示書をフィルタリングして表示する。ここまでは OK。次ビレットストックの表。これを作るのはいいが、いまいち 3 号機の状態が良く分からない。ここは明日相談。

# 2025/02/24

久しぶりに、再構築開始。ビレットストックを追加するときのバリデーションを完成させる。ビレットのロット番号入れるとき、SMC の場合、過去の実績値を出す。その為には、材質も把握しなければならない。PressDirective を選んだ時、ビレットサイズ、材質を引き継ぐ。材質の送信、受領 OK。次は、ビレット番号を選択式にする。サブウィンドウを開いて選択式にしないとだめ。少しめんどくさいので、下に候補を示すテーブルを表示しよう。

# 2025/03/06

`ProductionNumberV3`を使ってみて、以下の問題点。

- 品番修正時、修正モードであるにもかかわらず、'Save'ボタンがアクティブになり、間違えて押してしまう。
- `Update`モードに入っているにもかかわらず、`Update`ボタンがアクティブにならない。
- データを最初に読み込んでいるのか？修正後のデータがリロードしないと反映しない。
- 削除時、削除してもリストが反映しない。
- Delete は独立させた方がいい

# 2025/05/06

金型メンテナンスページを作りたい。index.html はヘッダーと中身を変えたが、これ、読み込み異常がでてタブレットで使えないので、従来の作り方に変更したい。

まずは、今の動きの遅い SQL を作り直す。押出が終わった金型の抽出。100 回分ぐらいあればいいかな？

これがメインテーブルの`SQL`。だいぶややこしい。一か所目のややこしさは、前回金型を洗浄してから、何回目の押出になっているか？だろう。`t_press` から引っ張ってきているが、これは、さすがにベースは、`m_dies`でやるべきではないか？

```SQL
SELECT
    t_press.dies_id,
    m_dies.die_number,
    SUM(CASE WHEN(
                CONCAT(t_press.press_date_at, ' ', DATE_FORMAT(t_press.press_start_at, '%H:%i')) > (
                    SELECT
                        MAX(IFNULL(t_dies_status.do_sth_at, '2000-01-01 00:00')) AS do_sth_date
                    FROM
                        m_dies
                        LEFT JOIN
                            t_dies_status
                        ON  t_dies_status.dies_id = m_dies.id
                    WHERE
                        m_dies.id = t_press.dies_id
                    AND (
                            t_dies_status.die_status_id = 4
                        OR  t_dies_status.die_status_id = 10
                        )
                    GROUP BY
                        m_dies.id
                )
            ) THEN 1 ELSE 0 END) AS is_washed_die,
    CONCAT(t10.die_status, ' ', IFNULL(t10.tank, '')) AS die_status,
    t10.die_status_id,
    SUBSTRING_INDEX(staff_name, ' ', - 1) AS name,
    t10.note,
    DATE_FORMAT(t10.do_sth_at, '%y-%m-%d %H:%i') AS do_sth_at,
    t10.specific_value
FROM
    t_press
    LEFT JOIN
        m_dies
    ON  t_press.dies_id = m_dies.id
    LEFT JOIN
        (
            SELECT
                t_dies_status.dies_id,
                m_die_status.die_status,
                t_dies_status.die_status_id,
                t_dies_status.do_sth_at,
                t_dies_status.note,
                t_dies_status.staff_id,
                m_staff.staff_name,
                t_dies_status.tank,
                t_dies_status.specific_value
            FROM
                t_dies_status
                LEFT JOIN
                    m_die_status
                ON  t_dies_status.die_status_id = m_die_status.id
                LEFT JOIN
                    m_staff
                ON  t_dies_status.staff_id = m_staff.id
                LEFT JOIN
                    (
                        SELECT
                            t_dies_status.dies_id,
                            t_dies_status.die_status_id,
                            MAX(t_dies_status.do_sth_at) AS do_sth_at,
                            t_dies_status.staff_id,
                            t_dies_status.tank
                        FROM
                            t_dies_status
                        GROUP BY
                            t_dies_status.dies_id
                    ) AS t10
                ON  t_dies_status.dies_id = t10.dies_id
                AND t_dies_status.do_sth_at = t10.do_sth_at
            WHERE
                t10.dies_id IS NOT NULL
        ) AS t10
    ON  t10.dies_id = t_press.dies_id
GROUP BY
    dies_id
ORDER BY
    CASE die_status
        WHEN 'Grinding' THEN 9
        WHEN 'Wire cutting' THEN 8
        WHEN 'NG' THEN 7
        WHEN 'NG Rz/Die mark' THEN 6
        WHEN 'NG Kích thước' THEN 5
        WHEN 'Washing' THEN 4
        WHEN 'OK' THEN 3
        WHEN 'Measuring' THEN 2
        WHEN 'On rack' THEN 1
        ELSE 0
    END DESC,
    is_washed_die DESC,
    die_number ASC
;
```

完全ではないが以下である程度コピーできたのではないか？

```SQL
WITH q_last_wash_date AS (
    SELECT
        t_dies_status.dies_id,
        MAX(t_dies_status.do_sth_at) AS last_wash_date
    FROM
        t_dies_status
    WHERE
        t_dies_status.die_status_id = 4
    GROUP BY
        t_dies_status.dies_id
),
press_date_query AS (
    SELECT
        t_press.dies_id,
        CONCAT(t_press.press_date_at, " ", t_press.press_finish_at) AS press_date
    FROM
        t_press
),
press_cnt_query AS (
    SELECT
        q_last_wash_date.dies_id,
        COUNT(press_date_query.dies_id) AS press_cnt
    FROM
        q_last_wash_date
        LEFT JOIN press_date_query
        ON q_last_wash_date.dies_id = press_date_query.dies_id
    WHERE
        q_last_wash_date.last_wash_date < press_date_query.press_date
    GROUP BY q_last_wash_date.dies_id
),
update_info_query AS (
    SELECT
        t1.dies_id,
        DATE_FORMAT(t1.do_sth_at, '%y/%m/%d') AS update_date,
        m_staff.staff_name,
        m_die_status.die_status,
        t1.die_status_id
    FROM t_dies_status AS t1
    LEFT JOIN m_staff
        ON t1.staff_id = m_staff.id
    LEFT JOIN m_die_status
        ON t1.die_status_id = m_die_status.id
    WHERE t1.do_sth_at = (
        SELECT MAX(t2.do_sth_at)
        FROM t_dies_status AS t2
        WHERE t1.dies_id = t2.dies_id
    )
)
SELECT
    m_dies.id,
    m_dies.die_number,
    IFNULL(press_cnt_query.press_cnt, 0) AS press_cnt,
    update_info_query.update_date,
    update_info_query.staff_name,
    ifnull(update_info_query.die_status_id, 0) AS dies_status_id,
    update_info_query.die_status
FROM
    m_dies
LEFT JOIN press_cnt_query
    ON press_cnt_query.dies_id = m_dies.id
LEFT JOIN update_info_query
    ON update_info_query.dies_id = m_dies.id
ORDER BY FIELD(dies_status_id, 4, 2, 10, 8, 0), die_number
```

ただ、これを見ていると、プレスが終わった金型が分からない。on_rack の金型を押出ししたら、on_rack では無いという事。
つまり、金型メンテナンス作業に見合った表示にしていかないとならない。押出が終わっても、洗浄しないで、on_rack した金型は、現状は押出可能になる。でも、押出が終わって、その後、何の情報もない金型は、現場に放置されているという事。まずは、その金型を上位に表示すべきではないか？それとは別に型別に、前回の洗浄から何回押出したかは表示する。
型別の押出日、と型別の最新情報を比較して、押出日以降の記録が無い場合、その金型は洗浄もされていないという事。押出が終わった金型は、洗浄するか、ラックに保管するか？のどちらかを選ぶ。洗浄した金型は、メンテナンスして、ラックに戻す。
では、型別の最終押出日を抽出する`SQL`、、、苦手な奴。以下、完成形

```sql
#########################
# 型別、最終押出日の抽出SQL
SELECT
	t1.id,
	t1.dies_id,
	t1.press_date_at
FROM
	t_press AS t1
WHERE
	concat(t1.press_date_at, " ", t1.press_start_at) = (
		SELECT MAX(CONCAT(t2.press_date_at, " ", t2.press_start_at))
		FROM t_press AS t2
		WHERE t1.dies_id = t2.dies_id
	)
ORDER BY t1.dies_id
;
```

次は、型別の型情報。

```SQl
WITH dies_last_pressed_date_query AS (
	SELECT
		t1.id,
		t1.dies_id,
		t1.press_date_at
	FROM
		t_press AS t1
	WHERE
		concat(t1.press_date_at, " ", t1.press_start_at) = (
			SELECT MAX(CONCAT(t2.press_date_at, " ", t2.press_start_at))
			FROM t_press AS t2
			WHERE t1.dies_id = t2.dies_id
		)
	ORDER BY t1.dies_id
	) , dies_last_status_date_query AS (
	SELECT
		t1.id,
		t1.dies_id,
		t1.do_sth_at
	FROM t_dies_status AS t1
	WHERE t1.do_sth_at = (
		SELECT MAX(t2.do_sth_at)
		FROM t_dies_status AS t2
		WHERE t1.dies_id = t2.dies_id
		)
	)
SELECT *
FROM dies_last_pressed_date_query
INNER JOIN dies_last_status_date_query
 ON dies_last_pressed_date_query.dies_id = dies_last_status_date_query.dies_id
;
```

これで型別の最新の`status`がいつ入力されているか明確になった。なので、押出したけど、`status`が入力されていない金型を抽出すればいい。以下がその`SQL`。

```SQL
WITH dies_last_pressed_date_query AS (
	SELECT
		t1.id,
		t1.dies_id,
		concat(t1.press_date_at, " ", t1.press_start_at) AS press_date_at
	FROM
		t_press AS t1
	WHERE
		concat(t1.press_date_at, " ", t1.press_start_at) = (
			SELECT MAX(CONCAT(t2.press_date_at, " ", t2.press_start_at))
			FROM t_press AS t2
			WHERE t1.dies_id = t2.dies_id
		)
	ORDER BY t1.dies_id
	) , dies_last_status_date_query AS (
	SELECT
		t1.id,
		t1.dies_id,
		t1.do_sth_at,
		t1.die_status_id
	FROM t_dies_status AS t1
	WHERE t1.do_sth_at = (
		SELECT MAX(t2.do_sth_at)
		FROM t_dies_status AS t2
		WHERE t1.dies_id = t2.dies_id
		)
	)
SELECT *
FROM dies_last_pressed_date_query
INNER JOIN dies_last_status_date_query
	ON dies_last_pressed_date_query.dies_id = dies_last_status_date_query.dies_id
WHERE dies_last_pressed_date_query.press_date_at > dies_last_status_date_query.do_sth_at
ORDER BY dies_last_pressed_date_query.dies_id
;
```

これで、結構な金型が出てくる。確かに、押出後の情報がなさそう。

```sql
WITH dies_last_pressed_date_query AS(
    SELECT
        t1.id,
        t1.dies_id,
        concat(t1.press_date_at, " ", t1.press_start_at) AS press_date_at
    FROM
        t_press AS t1
    WHERE
        concat(t1.press_date_at, " ", t1.press_start_at) = (
            SELECT
                MAX(CONCAT(t2.press_date_at, " ", t2.press_start_at))
            FROM
                t_press AS t2
            WHERE
                t1.dies_id = t2.dies_id
        )
    ORDER BY
        t1.dies_id
),
dies_last_status_date_query AS(
    SELECT
        t1.id,
        t1.dies_id,
        t1.do_sth_at,
        t1.die_status_id
    FROM
        t_dies_status AS t1
    WHERE
        t1.do_sth_at = (
            SELECT
                MAX(t2.do_sth_at)
            FROM
                t_dies_status AS t2
            WHERE
                t1.dies_id = t2.dies_id
        )
)
SELECT
    dies_last_pressed_date_query.dies_id,
    date_format(dies_last_pressed_date_query.press_date_at, '%y/%m/%d') AS press_date_at,
    m_dies.die_number
FROM
    dies_last_pressed_date_query
    INNER JOIN
        dies_last_status_date_query
    ON  dies_last_pressed_date_query.dies_id = dies_last_status_date_query.dies_id
    LEFT JOIN
        m_dies
    ON  dies_last_pressed_date_query.dies_id = m_dies.id
WHERE
    dies_last_pressed_date_query.press_date_at > dies_last_status_date_query.do_sth_at
    #ORDER BY dies_last_pressed_date_query.dies_id
ORDER BY
    press_date_at DESC
;
```

これで出てくる中に、同じ時間に、on_rack と、nitriding の両方をやっている金型が有る。もう一つ、めんどくさい事に、同じ日に、同じ処理をしているものがある。もう一つ、日付の比較が上手くいっていないかも。文字列と、日付型の比較になっているのが問題かも。
苦肉の策だがやや修正して精度を上げたもの

```sql
WITH dies_last_pressed_date_query AS (
	SELECT
        t1.id,
        t1.dies_id,
        t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND AS press_date_at
    FROM
        t_press AS t1
    WHERE
        t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND = (
            SELECT
                MAX(t2.press_date_at + INTERVAL TIME_TO_SEC(t2.press_start_at) SECOND)
            FROM
                t_press AS t2
            WHERE
                t1.dies_id = t2.dies_id
        )
    ORDER BY
        t1.dies_id
	) , dies_last_status_date_query AS (
	SELECT
		t1.id,
		t1.dies_id,
		t1.do_sth_at,
		t1.die_status_id
	FROM t_dies_status AS t1
	WHERE t1.do_sth_at = (
		SELECT MAX(t2.do_sth_at)
		FROM t_dies_status AS t2
		WHERE t1.dies_id = t2.dies_id
            AND
            t2.die_status_id IN (4, 10)
		)
	)
SELECT
	dies_last_pressed_date_query.dies_id,
	date_format(dies_last_pressed_date_query.press_date_at, '%y/%m/%d') AS press_date_at,
	m_dies.die_number
FROM dies_last_pressed_date_query
INNER JOIN dies_last_status_date_query
	ON dies_last_pressed_date_query.dies_id = dies_last_status_date_query.dies_id
LEFT JOIN m_dies
	ON dies_last_pressed_date_query.dies_id = m_dies.id
WHERE
	dies_last_pressed_date_query.press_date_at > dies_last_status_date_query.do_sth_at
	AND
	dies_last_status_date_query.die_status_id != 8
ORDER BY press_date_at desc
```

ただ、これだと、これまでに何回押出したか、不明。前回の洗浄記録から、何回の押出をしたのか、算出する必要がある。

```sql
# 型別の押出回数
SELECT
	t3.dies_id,
	COUNT(*) as no_wash_press
FROM t_press AS t3
WHERE t3.press_date_at + INTERVAL TIME_TO_SEC(t3.press_start_at) SECOND >
	(
	SELECT
		t1.do_sth_at
	FROM t_dies_status AS t1
	WHERE t3.dies_id = t1.dies_id
			and
			t1.do_sth_at = (
		SELECT MAX(t2.do_sth_at)
		FROM t_dies_status AS t2
		WHERE t1.dies_id = t2.dies_id
            AND
            t2.die_status_id = 4
		)
	)

GROUP BY t3.dies_id
;
```

結構出てくる。2 型確認したが、確かに洗浄の記録が無い。恐らく、洗浄の記録が正しく入っていない。結論

```sql
WITH dies_last_pressed_date_query AS (
	SELECT
        t1.id,
        t1.dies_id,
        t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND AS press_date_at
    FROM
        t_press AS t1
    WHERE
        t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND = (
            SELECT
                MAX(t2.press_date_at + INTERVAL TIME_TO_SEC(t2.press_start_at) SECOND)
            FROM
                t_press AS t2
            WHERE
                t1.dies_id = t2.dies_id
        )
    ORDER BY
        t1.dies_id
	) , dies_last_status_date_query AS (
	SELECT
		t1.id,
		t1.dies_id,
		t1.do_sth_at,
		t1.die_status_id
	FROM t_dies_status AS t1
	WHERE t1.do_sth_at = (
		SELECT MAX(t2.do_sth_at)
		FROM t_dies_status AS t2
		WHERE t1.dies_id = t2.dies_id
            AND
            t2.die_status_id IN (4, 10)
		)
	) , dies_no_wash_press_time_query AS (

	SELECT
		t3.dies_id,
		COUNT(*) as no_wash_press
	FROM t_press AS t3
	WHERE t3.press_date_at + INTERVAL TIME_TO_SEC(t3.press_start_at) SECOND >
		(
		SELECT
			t1.do_sth_at
		FROM t_dies_status AS t1
		WHERE t3.dies_id = t1.dies_id
				and
				t1.do_sth_at = (
			SELECT MAX(t2.do_sth_at)
			FROM t_dies_status AS t2
			WHERE t1.dies_id = t2.dies_id
	            AND
	            t2.die_status_id = 4
			)
		)

	GROUP BY t3.dies_id


	)
SELECT
	dies_last_pressed_date_query.dies_id,
	date_format(dies_last_pressed_date_query.press_date_at, '%y/%m/%d') AS press_date_at,
	ifnull(dies_no_wash_press_time_query.no_wash_press, 0) AS no_wash_press,
	m_dies.die_number
FROM dies_last_pressed_date_query
INNER JOIN dies_last_status_date_query
	ON dies_last_pressed_date_query.dies_id = dies_last_status_date_query.dies_id
left JOIN dies_no_wash_press_time_query
	ON dies_no_wash_press_time_query.dies_id = dies_last_status_date_query.dies_id
LEFT JOIN m_dies
	ON dies_last_pressed_date_query.dies_id = m_dies.id
WHERE
	dies_last_pressed_date_query.press_date_at > dies_last_status_date_query.do_sth_at
	AND
	dies_last_status_date_query.die_status_id != 8
ORDER BY press_date_at DESC
;
```

これも正しくないかも。押出後、ラックに載せていればそれも問題ない。つまり、最終の押出記録以降に、洗浄記録が無い又はラックに載せた記録が無い。いや、ちゃんと、ラックに載せているか、洗浄の記録があるものは、リスト対象外になっている。

次は、洗浄が終わった金型のリストアップ。押出の終わった金型のリストアップの様に、洗浄は終わったが、次の工程が入力されていない金型。型別の、最終洗浄日のリストアップと、型別の、洗浄日以外の最終情報（ラックとか、grind とか）のリストアップ。

```sql
SELECT
    t1.id,
    t1.dies_id,
    t1.do_sth_at,
    t1.die_status_id
FROM
    t_dies_status AS t1
WHERE
	t1.do_sth_at =
    (SELECT
            MAX(t2.do_sth_at) AS last_wash_date_time
        FROM
            t_dies_status AS t2
        WHERE
            t2.die_status_id = 4
                AND t2.dies_id = t1.dies_id)
;
```

型別の、最終洗浄日の抽出 SQL。

```sql
WITH latest_wash_date_query AS(
    SELECT
        # t1.id,
        t1.dies_id,
        t1.do_sth_at,
        t1.die_status_id
    FROM
        t_dies_status AS t1
    WHERE
        t1.do_sth_at = (
            SELECT
                MAX(t2.do_sth_at) AS last_wash_date_time
            FROM
                t_dies_status AS t2
            WHERE
                t2.die_status_id = 4
            AND t2.dies_id = t1.dies_id
        )
),
latest_without_wash_date_query AS(
    SELECT
        # t1.id,
        t1.dies_id,
        t1.do_sth_at,
        t1.die_status_id
    FROM
        t_dies_status AS t1
    WHERE
        t1.do_sth_at = (
            SELECT
                MAX(t2.do_sth_at) AS last_wash_date_time
            FROM
                t_dies_status AS t2
            WHERE
                t2.die_status_id IN(5, 6, 7, 8, 9, 10)
            AND t2.dies_id = t1.dies_id
        )
)
SELECT
    latest_wash_date_query.dies_id,
    m_dies.die_number,
    date_format(latest_wash_date_query.do_sth_at, '%y/%m/%d') AS wash_date
FROM
    latest_wash_date_query
    INNER JOIN
        latest_without_wash_date_query
    ON  latest_wash_date_query.dies_id = latest_without_wash_date_query.dies_id
    LEFT JOIN
        m_dies
    ON  latest_wash_date_query.dies_id = m_dies.id
WHERE
    latest_wash_date_query.do_sth_at > latest_without_wash_date_query.do_sth_at
ORDER BY
    wash_date DESC,
    m_dies.die_number
```

こんな感じか。

少し問題があり、表を、INSERT する必要がある。どうやってやるんだっけ？一行一行やった方がいいかな？

```html
<table id="data-table">
  <tr>
    <td>Value 1</td>
    <td>Value 2</td>
  </tr>
  <tr>
    <td>Value 3</td>
    <td>Value 4</td>
  </tr>
  <tr>
    <td>Value 5</td>
    <td>Value 6</td>
  </tr>
</table>
<button id="send-data">データ送信</button>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $("#send-data").click(function () {
    const data = [];
    $("#data-table tr").each(function () {
      const row = [];
      $(this)
        .find("td")
        .each(function () {
          row.push($(this).text());
        });
      if (row.length > 0) {
        data.push(row);
      }
    });

    // AJAXでデータを送信
    $.ajax({
      url: "insert.php",
      method: "POST",
      data: { tableData: JSON.stringify(data) },
      success: function (response) {
        console.log("データ送信成功: " + response);
      },
      error: function (error) {
        console.log("エラー: " + error);
      },
    });
  });
</script>
```

これに対して

```php
<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "your_database_name";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("接続失敗: " . $conn->connect_error);
}

$tableData = json_decode($_POST['tableData']);

foreach ($tableData as $row) {
    $sql = "INSERT INTO your_table_name (column1, column2) VALUES ('$row[0]', '$row[1]')";
    if ($conn->query($sql) === TRUE) {
        echo "新しいレコードが作成されました";
    } else {
        echo "エラー: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>
```

となるそうである。かなり簡単。まずは、表の配列を作る事からかな。

```javascript
$("#wash_die__img").on("click", function () {
  const now = new Date();
  const hours = now.getHours();
  const minutes = now.getMinutes();
  const seconds = now.getSeconds();
  const currentTime = `${hours}:${minutes}:${seconds}`;
  const currentDayteTime = $("#washing_date__input").val() + " " + currentTime;
  const tankNumber = $("#tank_number__select").val();
  const data = [];
  var dieIdObj;

  dieIdObj = $("#after_press_dies__table tr.selected-record td:nth-child(1)");

  dieIdObj.each(function () {
    const row = [];
    data.push([$(this).html(), currentDayteTime, tankNumber]);
  });
  console.log(data);
});
```

データを送る側の表の配列の準備はこんな感じか。

もう一つ、右側に、洗浄中の金型を表示しないと。最新の情報が金型洗浄中なら、それは洗浄中。

```sql
SELECT
    t1.dies_id,
    m_dies.die_number,
    t1.tank,
    date_format(t1.do_sth_at, '%m/%d') as wash_date_at
FROM
    t_dies_status AS t1
    left join
        m_dies
    on  t1.dies_id = m_dies.id
WHERE
    t1.do_sth_at = (
        SELECT
            MAX(t2.do_sth_at)
        FROM
            t_dies_status AS t2
        WHERE
            t1.dies_id = t2.dies_id
        GROUP BY
            t2.dies_id
    )
and t1.die_status_id = 4
order by
    t1.do_sth_at desc;
```

デバッグ用に型の履歴を表示したのはいいが、同じ金型の押出履歴も出した方がいい。

```sql
SELECT
	t_press.id,
	m_dies.die_number,
	date_format(t_press.press_date_at, '%y/%m/%d') AS press_date
FROM t_press
LEFT JOIN m_dies
	ON t_press.dies_id = m_dies.id
WHERE t_press.dies_id = 123
ORDER BY press_date_at desc
;
```

洗浄記録を作る。まずは、タンク番号、洗浄日、金型選択がされているとき、ボタンをアクティブにする。

![](./img/20250520-01.png)
![](./img/20250520-02.png)

```
基本的に、

●と◎：金型洗浄終わり（ReadPressが表示）→押出可→押出実施：
➀OK：そのままで次回押出可（回数：０→１；状態：ReadyPress）
➁NG：ダイスマーク/成形不良など　→押出人、WEBに更新し（NG状態を選択する）→NeedWashing（洗浄必要）

○：初回テスト/修正後テスト/条件テストなど。。。
NGあったら→押出人、WEBに更新し（NG状態を選択する）→NeedWashing（洗浄必要）


ReadyPress状態は「押出回数：２回以下」
```

改造が必要な SQL はこちら。

```sql
WITH dies_last_pressed_date_query AS(
    SELECT
        t1.id,
        t1.dies_id,
        t1.pressing_type_id,
        t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND AS press_date_at
    FROM
        t_press AS t1
    WHERE
        t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND = (
            SELECT
                MAX(t2.press_date_at + INTERVAL TIME_TO_SEC(t2.press_start_at) SECOND)
            FROM
                t_press AS t2
            WHERE
                t1.dies_id = t2.dies_id
        )
    ORDER BY
        t1.dies_id
),
dies_last_status_date_query AS(
    SELECT
        t1.id,
        t1.dies_id,
        t1.do_sth_at,
        t1.die_status_id
    FROM
        t_dies_status AS t1
    WHERE
        t1.do_sth_at = (
            SELECT
                MAX(t2.do_sth_at)
            FROM
                t_dies_status AS t2
            WHERE
                t1.dies_id = t2.dies_id
            AND t2.die_status_id IN(4, 10)
        )
),
dies_no_wash_press_time_query AS(
    SELECT
        t3.dies_id,
        COUNT(*) as no_wash_press
    FROM
        t_press AS t3
    WHERE
        t3.press_date_at + INTERVAL TIME_TO_SEC(t3.press_start_at) SECOND > (
            SELECT
                t1.do_sth_at
            FROM
                t_dies_status AS t1
            WHERE
                t3.dies_id = t1.dies_id
            and t1.do_sth_at = (
                    SELECT
                        MAX(t2.do_sth_at)
                    FROM
                        t_dies_status AS t2
                    WHERE
                        t1.dies_id = t2.dies_id
                    AND t2.die_status_id = 4
                )
        )
    GROUP BY
        t3.dies_id
),
dies_id_last_diemark_ng_query AS(
    SELECT
        t1.dies_id,
        t1.do_sth_at
    FROM
        t_dies_status AS t1
        LEFT JOIN
            dies_last_pressed_date_query
        ON  dies_last_pressed_date_query.dies_id = t1.dies_id
    WHERE
        t1.do_sth_at = (
            SELECT
                MAX(t2.do_sth_at)
            FROM
                t_dies_status AS t2
            WHERE
                t1.dies_id = t2.dies_id
            and t2.die_status_id = 31
        )
    AND t1.do_sth_at > dies_last_pressed_date_query.press_date_at
)
SELECT
    dies_last_pressed_date_query.dies_id,
    date_format(dies_last_pressed_date_query.press_date_at, '%y/%m/%d') AS press_date_at,
    m_dies.die_number,
    m_pressing_type.pressing_type,
    ifnull(dies_no_wash_press_time_query.no_wash_press, 0) AS no_wash_press,
    # dies_id_last_diemark_ng_query.dies_id,
    if(dies_id_last_diemark_ng_query.dies_id IS NULL, '', 'NG') AS die_mark
FROM
    dies_last_pressed_date_query
    INNER JOIN
        dies_last_status_date_query
    ON  dies_last_pressed_date_query.dies_id = dies_last_status_date_query.dies_id
    left JOIN
        dies_no_wash_press_time_query
    ON  dies_no_wash_press_time_query.dies_id = dies_last_status_date_query.dies_id
    LEFT JOIN
        m_dies
    ON  dies_last_pressed_date_query.dies_id = m_dies.id
    LEFT JOIN
        m_pressing_type
    ON  dies_last_pressed_date_query.pressing_type_id = m_pressing_type.id
    LEFT join
        dies_id_last_diemark_ng_query
    ON  dies_last_pressed_date_query.dies_id = dies_id_last_diemark_ng_query.dies_id
WHERE
    dies_last_pressed_date_query.press_date_at > dies_last_status_date_query.do_sth_at
AND dies_last_status_date_query.die_status_id != 8
ORDER BY
    press_date_at DESC,
    die_number
```

デバッグ用の`input`窓を作ったので、少しデバッグしてみる。それと、メンバーを入力できるようにしたい。それも、ここ最近よくやっている人を上位に出すようにする。過去 1 か月分を元に、ソートしたい。

SELECT \*
FROM テーブル名
WHERE 日付カラム <= DATE_SUB(CURDATE(), INTERVAL 1 MONTH);

```sql
WITH experience_staff AS(
    SELECT
        t_dies_status.staff_id,
        COUNT(*) AS cnt
    FROM
        t_dies_status
    WHERE
        t_dies_status.die_status_id = 8
    AND t_dies_status.do_sth_at >= DATE_SUB(CURDATE(), INTERVAL 2 MONTH)
    GROUP BY
        t_dies_status.staff_id
)
SELECT
    m_staff.id,
    m_staff.staff_name,
    ifnull(experience_staff.cnt, 0) AS cnt
FROM
    m_staff
    LEFT JOIN
        experience_staff
    ON  experience_staff.staff_id = m_staff.id
ORDER BY
    cnt DESC,
    m_staff.id
;
```

こうかな、、、2 か月の内で、最も頻度が高い人を上位に出すようにした。スタッフリストの作成部分も OK。ただし、最初の読み込みがだいぶ時間がかかる。
判定用のカラムを追加したいが、だいぶ場所が無い。

![](./img/20250521-01.png)
この表に判定結果を示すカラムを追加したい。

```sql

WITH dies_last_pressed_date_query AS(
    SELECT
        t1.id,
        t1.dies_id,
        t1.pressing_type_id,
        t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND AS press_date_at
    FROM
        t_press AS t1
    WHERE
        t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND = (
            SELECT
                MAX(t2.press_date_at + INTERVAL TIME_TO_SEC(t2.press_start_at) SECOND)
            FROM
                t_press AS t2
            WHERE
                t1.dies_id = t2.dies_id
                AND
					 t2.press_date_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)

        ) and
        t1.press_date_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ORDER BY
        t1.dies_id
),
dies_last_status_date_query AS(
    SELECT
        t1.id,
        t1.dies_id,
        t1.do_sth_at,
        t1.die_status_id
    FROM
        t_dies_status AS t1
    WHERE
        t1.do_sth_at = (
            SELECT
                MAX(t2.do_sth_at)
            FROM
                t_dies_status AS t2
            WHERE
                t1.dies_id = t2.dies_id
            AND t2.die_status_id IN(4, 10)
        )
),
dies_no_wash_press_time_query AS(
    SELECT
        t3.dies_id,
        COUNT(*) as no_wash_press
    FROM
        t_press AS t3
    WHERE
        t3.press_date_at + INTERVAL TIME_TO_SEC(t3.press_start_at) SECOND > (
            SELECT
                t1.do_sth_at
            FROM
                t_dies_status AS t1
            WHERE
                t3.dies_id = t1.dies_id
            and t1.do_sth_at = (
                    SELECT
                        MAX(t2.do_sth_at)
                    FROM
                        t_dies_status AS t2
                    WHERE
                        t1.dies_id = t2.dies_id
                    AND t2.die_status_id = 4
                )
        )
    GROUP BY
        t3.dies_id
),
dies_id_last_diemark_ng_query AS(
    SELECT
        t1.dies_id,
        t1.do_sth_at
    FROM
        t_dies_status AS t1
        LEFT JOIN
            dies_last_pressed_date_query
        ON  dies_last_pressed_date_query.dies_id = t1.dies_id
    WHERE
        t1.do_sth_at = (
            SELECT
                MAX(t2.do_sth_at)
            FROM
                t_dies_status AS t2
            WHERE
                t1.dies_id = t2.dies_id
            and t2.die_status_id IN(31, 32)
        )
    AND t1.do_sth_at > dies_last_pressed_date_query.press_date_at
)
SELECT
    dies_last_pressed_date_query.dies_id,
    date_format(dies_last_pressed_date_query.press_date_at, '%m/%d') AS press_date_at,
    m_dies.die_number,
    m_pressing_type.pressing_type,
    ifnull(dies_no_wash_press_time_query.no_wash_press, 0) AS no_wash_press,
    # dies_id_last_diemark_ng_query.dies_id,
    if(dies_id_last_diemark_ng_query.dies_id IS NULL, '', 'NG') AS die_mark,
    case
    	when m_pressing_type.id = 1
#    		then '〇'
			then if(dies_id_last_diemark_ng_query.dies_id IS NOT NULL, 'Wash', 'Rack')
    	when m_pressing_type.id = 2
#    		then '◎'
			then if((dies_id_last_diemark_ng_query.dies_id IS not NULL) or (no_wash_press > 1), 'Wash', 'Rack')
    	when m_pressing_type.id = 3
#    		then '●'
			then if((dies_id_last_diemark_ng_query.dies_id IS not NULL) or (no_wash_press > 1), 'Wash', 'Rack')
#			then if((no_wash_press > 1), 'Wash', 'Rack')

   END AS action

FROM
    dies_last_pressed_date_query
    INNER JOIN
        dies_last_status_date_query
    ON  dies_last_pressed_date_query.dies_id = dies_last_status_date_query.dies_id
    left JOIN
        dies_no_wash_press_time_query
    ON  dies_no_wash_press_time_query.dies_id = dies_last_status_date_query.dies_id
    LEFT JOIN
        m_dies
    ON  dies_last_pressed_date_query.dies_id = m_dies.id
    LEFT JOIN
        m_pressing_type
    ON  dies_last_pressed_date_query.pressing_type_id = m_pressing_type.id
    LEFT join
        dies_id_last_diemark_ng_query
    ON  dies_last_pressed_date_query.dies_id = dies_id_last_diemark_ng_query.dies_id
WHERE
    dies_last_pressed_date_query.press_date_at > dies_last_status_date_query.do_sth_at
AND dies_last_status_date_query.die_status_id != 8
ORDER BY
    dies_last_pressed_date_query.press_date_at DESC,
    die_number
```

これが今使っている最終形。直近 1 年以内に絞った。多分、ソート機能が必要。その前に、入力の部分を進める。入力は出来るようになった。まだ戻らないけど、、、処理が終わったら、該当する金型をハッチングしている方がいい。どこにインサートされたか分からないから。

Rack の入力も出来るようにしよう。という事で、Washing タンクと、ラックの入れかをしたい。では、現在ラッキングしている金型を出したい。金型別の最新の情報が

```sql
SELECT
    t1.dies_id,
    m_dies.die_number,
    date_format(t1.do_sth_at, '%m/%d') AS on_rack
FROM
    t_dies_status AS t1
    LEFT JOIN
        m_dies
    ON  t1.dies_id = m_dies.id
WHERE
    t1.do_sth_at = (
        SELECT
            MAX(t2.do_sth_at) AS do_sth_at
        FROM
            t_dies_status AS t2
        WHERE
            t1.dies_id = t2.dies_id
            AND
            t2.die_status_id = 10
    )
ORDER BY
    date_format(t1.do_sth_at, '%y-%m-%d') DESC,
    die_number
LIMIT 50
;
```

表の切替も OK。時間切れの場合、元の表にするのも OK。次は、washing タンクに移した場合、右側の表でその金型を表示する。必要なのは、金型 ID、と処理日に一致する部分を反転する機能。これ出来たけど、`die_id`を元に判断してしまうと、`t_die_status`からデータを消去するときに問題になるね。同じ金型で次に情報が入っているとき、そこを消すことが出来ない。なので、`t_die_status`の id を取っていないとだめですね。

![](./img/20250524-01.png)

`Washing Tank`も、`t_die_status.id`を表にする。OK。次は、右ウィンドウから左に戻す方。戻すのは出来た。次は、戻した金型に色を塗る事。完了。次は、右ウィンドウに検索用の input 要素を加えること。OK。次は、Racking をする人の、option を最適化すること。スタッフのリストも最適化できた。ほぼ、終わりが見えてきた。最後は、金型の履歴。これは、`press`と`status`の両方を見たい。日付、プレスまたは、status 情報。

```sql
SELECT
#    date_time,
    date_format(date_time, '%y/%m/%d %H:%i') AS date_time1,
#    die_status_id,
    m_die_status.die_status
FROM
    (
        SELECT
            cast(concat(t_press.press_date_at, ' ', t_press.press_start_at) AS DATETIME) AS date_time,
            11 AS die_status_id
        FROM
            t_press
        WHERE
            t_press.dies_id = @die_id
        UNION
        SELECT
            t_dies_status.do_sth_at AS date_time,
            t_dies_status.die_status_id
        FROM
            t_dies_status
        WHERE
            t_dies_status.dies_id = @die_id
    ) AS t1
    LEFT JOIN
        m_die_status
    on  t1.die_status_id = m_die_status.id
ORDER BY
    t1.date_time desc
```

有る金型の t_press と t_dies_status を合わせた表。これを実行するには、下記、INSERT を実行する事

```sql
INSERT INTO m_die_status (id, die_status) VALUES (11, 'Press')
```

選択した金型の履歴の表示 OK。ほぼこのページは完成した？
完成していない。洗浄した金型をラックに入れる所が無い。まずは、Rack モードの時、tank ナンバーは必要ない。色々考えたが、一つ下に、左側にタンク、右側にラックの表を作るのがいいだろう。

色々不具合。右の表から戻すことが出来なくなっている。

色々ありすぎたので、位置から作り直している。アクティブな表を作ることとしよう。

テーブルの下に、タンクナンバーとかセレクトが欲しい。
そこらへんは終わって、`activate`も OK。

次は、`after-press`テーブルでクリックした場合、`washing`テーブルに移動するところ。

一つ目、〇のフォントを変えてみるか？変えた、違いが分からない。
次は、選択した金型を戻すところ。

```javascript
sendData = {
  // dieStatudId: JSON.stringify(dieStatusId),
  dieStatudId: dieStatusId,
};
```

配列を、`php`に送るところでかなり躓く。コメントアウトしているのが、以前のやり方。今回の配列は、`dieStatusId`だけを送っているので、直接送ってしまっていいみたい。これが表形式の配列を送る場合は、コメントアウトしている方を使うべき。

次は、`cancel`ボタンを active にする方法。
まずは、両方の金型を選択できないようにすべきでは？washing までは出来た。次は、racking。このプログラムのこの部分は良くないね。。。

```javascript
$(document).on(
  "click",
  "table#washing_dies__table thead, table#racking_dies__table thead",
  function () {
    if ($(this).parent("table").prop("class") === "inactive__table") {
      let idName;
      idName = $(this).parent("table").prop("id");
      switch (idName) {
        case "racking_dies__table": // racking mode
          $("#racking_dies__table").removeClass("inactive__table");
          $("#washing_dies__table").addClass("inactive__table");
          $("caption.washing-dies__caption").addClass("inactive__caption");
          $("caption.racking-dies__caption").removeClass("inactive__caption");
          $("#racking__div").removeClass("inactive__div");
          $("#washing__div").addClass("inactive__div");
          $("#washing__div select").val("0").addClass("required-input");

          $("#after_press_dies__table .selected-record").removeClass(
            "selected-record"
          );

          // make staff select
          staffOrderMode = 10;
          makeRackingStaffSelect();

          break;
        case "washing_dies__table": // washing mode
          $("#washing_dies__table").removeClass("inactive__table");
          $("#racking_dies__table").addClass("inactive__table");
          $("caption.racking-dies__caption").addClass("inactive__caption");
          $("caption.washing-dies__caption").removeClass("inactive__caption");
          $("#washing__div").removeClass("inactive__div");
          $("#racking__div").addClass("inactive__div");

          $("#after_press_dies__table .selected-record").removeClass(
            "selected-record"
          );
          break;
      }
    }
  }
);
```

それぞれ分けて作った方がいい。二つをまとめて作る意味がない。地味だが矢印の file ネームを数字で分けるのは分かりにくい。次、racking から戻す方。ほぼ、やりきったかな。

各テーブルの、検索も付けた。
いよいよ file＿name のテーブルを作る

```sql
CREATE TABLE t_dies_status_filename (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(100),
    time_stamp TIMESTAMP,
    t_dies_status_id INT
);
```

```sql
ALTER TABLE t_dies_status_filename
ADD CONSTRAINT fk_t_dies_status
FOREIGN KEY (t_dies_status_id)
REFERENCES
```

上ではエラーが出るかな、、、

```sql
ALTER TABLE `t_dies_status_filename`
	ADD CONSTRAINT `FK_t_dies_status_filename_t_dies_status` FOREIGN KEY (`t_dies_status_id`) REFERENCES `t_dies_status` (`id`);
```

ファイルのアップロード。アップロードプログラム自身はあっさり動く。次は、アップロードされたファイル名の表示。アップロードされた写真を拡大する機能の実装。
その為には、クリックされたファイルの`alt`属性にファイル名を付けること。

型修正用のスタッフリストを作る。
保存するところまで完了。もう少し仕上げ、、、だいぶできた。次は TEST ボタンの activeation

# 2025/06/08

次は、金型の履歴の表示。写真の表示も出来るようになった。
写真の保存先を、従来の場所と同じフォルダーにできた。次は、`t_dies_status`に入っている写真をどう表示するか？
一番簡単なのは、写真の呼び出しの`sql`に従来の写真情報も読み出せてしまうように改造。

```sql
        SELECT
          t_dies_status_filename.file_name
        FROM t_dies_status_filename
        WHERE t_dies_status_filename.t_dies_status_id = :die_status_id
```

このぐらいなら、出来そうだが、、

```sql
WITH all_data_table AS(
    SELECT
        t_dies_status_filename.file_name
    FROM
        t_dies_status_filename
    WHERE
        t_dies_status_filename.t_dies_status_id = 20082
    UNION
    SELECT
        t_dies_status.file_url AS file_name
    FROM
        t_dies_status
    WHERE
        t_dies_status.id = 20082
)
SELECT
    *
FROM
    all_data_table
WHERE
    all_data_table.file_name IS NOT NULL
```

こんな感じ

# V3 製作開始

V2 は何とか終わったが、内容が非常に不甲斐ない。少し丁寧に作って、全体をすっきりさせたい。まずは、翻訳機能の実装。今のままだと、ページ別の名前の割り振りが出来ずに短縮系の使い方が上手くいかない。ので、カラムを一つ追加する。

```sql
ALTER TABLE m_title_name
ADD COLUMN page_number INT;
```

まずは、レイアウトを全体的に完成させるべき。そのうえで、共通で使える部分は共通に書いていく。V3 作成中。

窒化も加えられないか、見てみよう。
二つの表にデータがバラバラになっているので、これを比較してみる。`t_dies_status`にはほとんど窒化の情報は入ってきていない。なので`t_nitriding`を見ればいい。そうすると、型別の前回窒化からの押出長さを算出する。

```sql
with latest_nitriding_date_by_dies_id as (
  SELECT
    m_dies.id as dies_id,
    IFNULL(t10.nitriding_date_at, DATE_FORMAT('2021-1-1', '%Y-%d-%m')) as nitriding_date_at
  from m_dies
  left join (
    select
      t10.dies_id,
      t10.nitriding_date_at
    from t_nitriding as t10
    where t10.nitriding_date_at = (
      select
        max(t2.nitriding_date_at)
      from t_nitriding as t2
      where t2.dies_id = t10.dies_id
      group by t2.dies_id
      )
    ) as t10
  on m_dies.id = t10.dies_id
), dies_id_and_production_weight as (
select
  m_dies.id as dies_id,
  m_production_numbers.specific_weight
from m_dies
left join m_production_numbers
  on m_dies.production_number_id = m_production_numbers.id
)
select
  t1.dies_id,
  m_dies.die_number,
  ROUND(SUM((3.14159 * POWER(t1.billet_size * 25.4 / 2, 2)
    * t1.billet_length * 0.001 * 2.70 * t1.actual_billet_quantities / 1000)
    / specific_weight /1000), 2) as length_km,
  count(t1.id) as press_count
from t_press as t1
left join dies_id_and_production_weight
  on t1.dies_id = dies_id_and_production_weight.dies_id
left join m_dies
  on t1.dies_id = m_dies.id
where t1.press_date_at >
  (
    select latest_nitriding_date_by_dies_id.nitriding_date_at
    from latest_nitriding_date_by_dies_id
    where latest_nitriding_date_by_dies_id.dies_id = t1.dies_id
  )
group by t1.dies_id
order by length_km desc
;
select
  t1.dies_id,
  t1.nitriding_date_at
from t_nitriding as t1
where t1.nitriding_date_at = (
  select
    max(t2.nitriding_date_at)
  from t_nitriding as t2
  where t2.dies_id = t1.dies_id
  group by t2.dies_id
  )
;
```

こんな感じではないだろうか。いや、もしかしたら、一回も窒化していないやつは、ここに出てこないとか。。。これは修正済み。さらに多数本押しは、その分減らさないとですね。それと、窒化後のプレス回数で管理していないですね。窒化後の洗浄回数。

```sql
with latest_nitriding_date_by_dies_id as (
  SELECT
    m_dies.id as dies_id,
    IFNULL(t10.nitriding_date_at, DATE_FORMAT('2021-1-1', '%Y-%d-%m')) as nitriding_date_at
  from m_dies
  left join (
    select
      t10.dies_id,
      t10.nitriding_date_at
    from t_nitriding as t10
    where t10.nitriding_date_at = (
      select
        max(t2.nitriding_date_at)
      from t_nitriding as t2
      where t2.dies_id = t10.dies_id
      group by t2.dies_id
      )
    ) as t10
  on m_dies.id = t10.dies_id
), dies_id_and_production_weight as (
select
  m_dies.id as dies_id,
  m_production_numbers.specific_weight
from m_dies
left join m_production_numbers
  on m_dies.production_number_id = m_production_numbers.id
), washing_count_after_nitriding_by_dies_id as (
select
  t20.dies_id,
  count(t20.dies_id) as washing_count_after_nitriding
from t_dies_status as t20
where
  t20.die_status_id = 4
  and
  t20.do_sth_at >
  (
    select
      latest_nitriding_date_by_dies_id.nitriding_date_at
    from
      latest_nitriding_date_by_dies_id
    where
      latest_nitriding_date_by_dies_id.dies_id = t20.dies_id
  )
group by t20.dies_id
order by t20.dies_id
)
select
  t1.dies_id,
  m_dies.die_number,
  ROUND(SUM((3.14159 * POWER(t1.billet_size * 25.4 / 2, 2)
    * t1.billet_length * 0.001 * 2.70 * t1.actual_billet_quantities / 1000)
    / specific_weight /1000 / m_dies.hole), 2) as length_km_after_nitiriding,
  count(t1.id) as press_count_after_nitriding,
  ifnull(washing_count_after_nitriding, 0) as washing_count_after_nitiriding
from t_press as t1
left join dies_id_and_production_weight
  on t1.dies_id = dies_id_and_production_weight.dies_id
left join m_dies
  on t1.dies_id = m_dies.id
left join washing_count_after_nitriding_by_dies_id
  on t1.dies_id = washing_count_after_nitriding_by_dies_id.dies_id
where t1.press_date_at >
  (
    select latest_nitriding_date_by_dies_id.nitriding_date_at
    from latest_nitriding_date_by_dies_id
    where latest_nitriding_date_by_dies_id.dies_id = t1.dies_id
  )
group by t1.dies_id
order by length_km_after_nitiriding desc
;
```

これで出来るように放ったが、これに total lenth とか total washing とか入れるとなると、更に長くなる。元々、t_dies の左結合が出来るようになるべきじゃないか。作り直すかな、、、考え方として、型別の、前回の窒化からの、押出距離、洗浄回数、と押出距離、洗浄回数。
では、まずは、`m_dies`へ左結合できるように前回の窒化からの押出距離、洗浄回数を出してみよう。押出距離は出た。次は洗浄回数。出来た。次は、トータルの押出距離。次は、型別の洗浄回数。

```sql
set @washing_die_status = 4;
set @specific_gravity_of_aluminum = 2.70;
set @pi = 3.141459;
set @inch = 25.4;
with latest_nitriding_date_by_dies_id as (
  SELECT
    m_dies.id as dies_id,
    IFNULL(t10.nitriding_date_at, DATE_FORMAT('2021-1-1', '%Y-%m-%d')) as nitriding_date_at
  from m_dies
  left join (
    select
      t10.dies_id,
      t10.nitriding_date_at
    from t_nitriding as t10
    where t10.nitriding_date_at = (
      select
        max(t2.nitriding_date_at)
      from t_nitriding as t2
      where t2.dies_id = t10.dies_id
      group by t2.dies_id
      )
    ) as t10
  on m_dies.id = t10.dies_id
), profile_length_after_nitriding_by_dies_id as (
  select
    t_press.dies_id,
    round(SUM(((@pi * POWER(t_press.billet_size * @inch / 2, 2)
      * t_press.billet_length * 0.001 * @spcific_grabity_of_alminium
      * t_press.actual_billet_quantities / 1000)
      / specific_weight /1000 / m_dies.hole)), 2) as length_km_after_nitiriding
  #  count(t_press.id) as press_count_after_nitriding
  from latest_nitriding_date_by_dies_id
  left join t_press
    on t_press.dies_id = latest_nitriding_date_by_dies_id.dies_id
  left join m_dies
    on latest_nitriding_date_by_dies_id.dies_id = m_dies.id
  left join m_production_numbers
    on m_dies.production_number_id = m_production_numbers.id
  where t_press.press_date_at > latest_nitriding_date_by_dies_id.nitriding_date_at
  group by t_press.dies_id
), washing_count_after_nitriding_by_dies_id as (
  select
    t_dies_status.dies_id,
    count(t_dies_status.dies_id) as washing_count_after_nitriding
  from t_dies_status
  left join latest_nitriding_date_by_dies_id
    on t_dies_status.dies_id = latest_nitriding_date_by_dies_id.dies_id
  where
    t_dies_status.die_status_id = @washing_die_status
    and
    t_dies_status.do_sth_at > latest_nitriding_date_by_dies_id.nitriding_date_at
  group by t_dies_status.dies_id
), dies_id_and_production_weight as (
select
  m_dies.id as dies_id,
  m_dies.hole,
  m_production_numbers.specific_weight
from m_dies
left join m_production_numbers
  on m_dies.production_number_id = m_production_numbers.id
), total_profile_length_by_dies_id as (
  select
    t_press.dies_id,
    round(SUM(((@pi * POWER(t_press.billet_size * @inch / 2, 2)
        * t_press.billet_length * 0.001 * @specific_gravity_of_aluminum
        * t_press.actual_billet_quantities / 1000)
        / specific_weight /1000 / hole)), 2) as total_profile_length
  from t_press
  left join dies_id_and_production_weight
    on t_press.dies_id = dies_id_and_production_weight.dies_id
  group by t_press.dies_id
), total_washing_count_by_dies_id as (
  select
    t_dies_status.dies_id,
    count(*) as count
  from t_dies_status
  where t_dies_status.die_status_id = 4
  group by t_dies_status.dies_id
)
select
  m_dies.id,
  m_dies.die_number,
  ifnull(profile_length_after_nitriding_by_dies_id.length_km_after_nitiriding, 0)
    as profile_length_after_nitriding,
  ifnull(washing_count_after_nitriding_by_dies_id.washing_count_after_nitriding, 0)
    as washing_count_after_nitriding,
  ifnull(total_profile_length_by_dies_id.total_profile_length, 0) as total_profile_length,
  ifnull(total_washing_count_by_dies_id.count, 0) as total_washing_count
from m_dies
left join profile_length_after_nitriding_by_dies_id
  on m_dies.id = profile_length_after_nitriding_by_dies_id.dies_id
left join washing_count_after_nitriding_by_dies_id
  on washing_count_after_nitriding_by_dies_id.dies_id = m_dies.id
left join total_profile_length_by_dies_id
  on total_profile_length_by_dies_id.dies_id = m_dies.id
left JOIN total_washing_count_by_dies_id
  on total_washing_count_by_dies_id.dies_id = m_dies.id
order by profile_length_after_nitriding_by_dies_id.length_km_after_nitiriding desc
```

これかな。。。
これをページの一番下に入れますか。。。html は出来た。
窒化の履歴と、型別の履歴も表示できた。
表のフィルター掛けをオブジェクト化できた。

次は、表のソート機能。
汎用的に、実装完了。で、一回も窒化していない金型が有るらしい。なので、単純に窒化の記録を一覧表示し、型番で検索できるようにする。

なんでか、after press table が表示されなくなる問題が発生。いま、改めてみてみると、なんとも無駄の多いというか、理解の苦しむ`SQL`になっているので、改定する。

ここは、現場に有る金型で、洗うか、ラッキング記録の無い金型。

- 型別の最新の押出の後に、洗い、ラッキングの情報が無い金型。
  まずは、型別の最新の押出の抽出。それらの金型の洗いか、ラッキングの最新情報が有れば、表示しない。

```sql
WITH latest_press_date_by_die_id AS(
  SELECT
    t1.id as press_id,
    t1.dies_id,
    t1.pressing_type_id,
    t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND AS press_date_at
  FROM
    t_press AS t1
  WHERE
    t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND = (
      SELECT
        MAX(t2.press_date_at + INTERVAL TIME_TO_SEC(t2.press_start_at) SECOND)
      FROM
        t_press AS t2
      WHERE
        t1.dies_id = t2.dies_id
      AND t2.press_date_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    )
  and t1.press_date_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
  ORDER BY
    t1.dies_id
), latest_washing_or_racking_date_by_die_id as (
  SELECT
    t1.id as t_dies_status_id,
    t1.dies_id,
    t1.do_sth_at,
    t1.die_status_id
  FROM
    t_dies_status AS t1
  WHERE
    t1.do_sth_at = (
      SELECT
        MAX(t2.do_sth_at)
      FROM
        t_dies_status AS t2
      WHERE
        t1.dies_id = t2.dies_id
      AND t2.die_status_id IN(@washing, @racking)
      and t2.do_sth_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    )
  and t1.do_sth_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
)
select
  latest_press_date_by_die_id.dies_id,
  latest_press_date_by_die_id.press_date_at,
  latest_washing_or_racking_date_by_die_id.die_status_id,
  latest_washing_or_racking_date_by_die_id.do_sth_at
from latest_press_date_by_die_id
left join latest_washing_or_racking_date_by_die_id
  on latest_press_date_by_die_id.dies_id = latest_washing_or_racking_date_by_die_id.dies_id
where latest_press_date_by_die_id.press_date_at > latest_washing_or_racking_date_by_die_id.do_sth_at
;
WITH latest_press_date_by_die_id AS(
  SELECT
    t1.id as press_id,
    t1.dies_id,
    t1.pressing_type_id,
    t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND AS press_date_at
  FROM
    t_press AS t1
  WHERE
    t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND = (
      SELECT
        MAX(t2.press_date_at + INTERVAL TIME_TO_SEC(t2.press_start_at) SECOND)
      FROM
        t_press AS t2
      WHERE
        t1.dies_id = t2.dies_id
      AND t2.press_date_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    )
  and t1.press_date_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
  ORDER BY
    t1.dies_id
), latest_washing_or_racking_date_by_die_id as (
  SELECT
    t1.id as t_dies_status_id,
    t1.dies_id,
    t1.do_sth_at,
    t1.die_status_id
  FROM
    t_dies_status AS t1
  WHERE
    t1.do_sth_at = (
      SELECT
        MAX(t2.do_sth_at)
      FROM
        t_dies_status AS t2
      WHERE
        t1.dies_id = t2.dies_id
      AND t2.die_status_id IN(@washing, @racking)
      and t2.do_sth_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    )
  and t1.do_sth_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
)
select
  latest_press_date_by_die_id.dies_id,
  latest_press_date_by_die_id.press_date_at,
  latest_washing_or_racking_date_by_die_id.die_status_id,
  latest_washing_or_racking_date_by_die_id.do_sth_at
from latest_press_date_by_die_id
left join latest_washing_or_racking_date_by_die_id
  on latest_press_date_by_die_id.dies_id = latest_washing_or_racking_date_by_die_id.dies_id
where latest_press_date_by_die_id.press_date_at > latest_washing_or_racking_date_by_die_id.do_sth_at
;

```

この SQL で押出後に、洗浄、または、ラッキングをしていない金型がリストアップされた。軸にしているのは die_id。もう一つ必要なのが、その押出を含め、前回の洗浄から何回目の押出になっているのか？また、その押出後に、NG 判定がされているのか？
つまり、最新の洗浄記録から、何回押出したのかを型別に出す SQL。

```sql
with latest_washing_date_by_dies_id as(
  select
    t1.dies_id as dies_id,
    t1.do_sth_at as washing_date
  from
    t_dies_status as t1
  where
    t1.die_status_id = @washing
  AND t1.do_sth_at = (
      select
        max(t2.do_sth_at)
      from
        t_dies_status as t2
      where
        t2.die_status_id = @washing
      and t2.dies_id = t1.dies_id
    )
)
select
  t3.dies_id,
  count(*) as pree_count_no_wash
from
  t_press as t3
  left join
    latest_washing_date_by_dies_id
  on  t3.dies_id = latest_washing_date_by_dies_id.dies_id
where
  latest_washing_date_by_dies_id.washing_date < t3.press_date_at + INTERVAL TIME_TO_SEC(t3.press_start_at) SECOND
group by
  t3.dies_id
```

これで型別の洗浄後の押出回数。

```sql
set @washing = 4;
with ranked_washing as (
  select
    t1.dies_id,
    t1.do_sth_at as washing_date,
    row_number() over (partition by t1.dies_id order by t1.do_sth_at desc) as rn
  from
    t_dies_status t1
  where
    t1.die_status_id = @washing
),
latest_washing_date_by_dies_id as (
  select dies_id, washing_date
  from ranked_washing
  where rn = 1
)
select
  t3.dies_id,
  count(*) as press_count_no_wash
from t_press as t3
left join latest_washing_date_by_dies_id latest
  on t3.dies_id = latest.dies_id
where latest.washing_date < t3.press_date_at + interval time_to_sec(t3.press_start_at) second
group by t3.dies_id
;
```

こっちの方が早いか。
残るは、押出後に NG 判定されている金型の抽出。

```sql
set @washing = 4;
set @ng_surface = 31;
set @ng_dimension = 32;

WITH latest_press_date_by_die_id AS(
  SELECT
    t1.id as press_id,
    t1.dies_id,
    t1.pressing_type_id,
    t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND AS press_date_at
  FROM
    t_press AS t1
  WHERE
    t1.press_date_at + INTERVAL TIME_TO_SEC(t1.press_start_at) SECOND = (
      SELECT
        MAX(t2.press_date_at + INTERVAL TIME_TO_SEC(t2.press_start_at) SECOND)
      FROM
        t_press AS t2
      WHERE
        t1.dies_id = t2.dies_id
      AND t2.press_date_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    )
  and t1.press_date_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
  ORDER BY
    t1.dies_id
), latest_washing_date_by_dies_id as(
  select
    t1.dies_id as dies_id,
    t1.do_sth_at as washing_date
  from
    t_dies_status as t1
  where
    t1.die_status_id = @washing
  AND t1.do_sth_at = (
      select
        max(t2.do_sth_at)
      from
        t_dies_status as t2
      where
        t2.die_status_id = @washing
      and t2.dies_id = t1.dies_id
    )
), latest_washing_or_racking_date_by_die_id as (
  SELECT
    t1.id as t_dies_status_id,
    t1.dies_id,
    t1.do_sth_at,
    t1.die_status_id
  FROM
    t_dies_status AS t1
  WHERE
    t1.do_sth_at = (
      SELECT
        MAX(t2.do_sth_at)
      FROM
        t_dies_status AS t2
      WHERE
        t1.dies_id = t2.dies_id
      AND t2.die_status_id IN(@washing, @racking)
      and t2.do_sth_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    )
  and t1.do_sth_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
), after_press_ng_dies_id as (
select
  t_dies_status.dies_id,
  t_dies_status.die_status_id
from t_dies_status
left join latest_press_date_by_die_id
  on t_dies_status.dies_id = latest_press_date_by_die_id.dies_id
where t_dies_status.die_status_id IN (@ng_surface, @ng_dimension)
  AND
  t_dies_status.do_sth_at > latest_press_date_by_die_id.press_date_at
),  after_press_dies as (
  select
    latest_press_date_by_die_id.dies_id,
    latest_press_date_by_die_id.press_date_at,
    latest_press_date_by_die_id.pressing_type_id

  from latest_press_date_by_die_id
  left join latest_washing_or_racking_date_by_die_id
    on latest_press_date_by_die_id.dies_id = latest_washing_or_racking_date_by_die_id.dies_id
  where latest_press_date_by_die_id.press_date_at > latest_washing_or_racking_date_by_die_id.do_sth_at
), latest_washing_by_die_id as (
  select
    t1.dies_id,
    t1.do_sth_at as washing_date,
    row_number() over (partition by t1.dies_id order by t1.do_sth_at desc) as rn
  from
    t_dies_status t1
  where
    t1.die_status_id = @washing
), press_count_after_wash as (
  select
    t3.dies_id,
    count(*) as press_count_no_wash
  from t_press as t3
  left join latest_washing_date_by_dies_id latest
    on t3.dies_id = latest.dies_id
  where latest.washing_date < t3.press_date_at + interval time_to_sec(t3.press_start_at) second
  group by t3.dies_id
)
select
  after_press_dies.dies_id,
  DATE_FORMAT(after_press_dies.press_date_at,'%m/%d') as press_date_at,
  m_dies.die_number,
  m_pressing_type.pressing_type,
  press_count_after_wash.press_count_no_wash,
  if(after_press_ng_dies_id.die_status_id is not null,"NG","OK"),
      case
    	when after_press_dies.pressing_type_id = 1
  			then if(after_press_ng_dies_id.die_status_id IS NOT NULL, 'Wash', 'Rack')
    	when m_pressing_type.id = 2
  			then if((after_press_ng_dies_id.die_status_id IS not NULL) or (press_count_no_wash > 1), 'Wash', 'Rack')
    	when m_pressing_type.id = 3
			  then if((after_press_ng_dies_id.die_status_id IS not NULL) or (press_count_no_wash > 1), 'Wash', 'Rack')
      end as action
from after_press_dies
left join press_count_after_wash
  on after_press_dies.dies_id = press_count_after_wash.dies_id
left join after_press_ng_dies_id
  on after_press_dies.dies_id = after_press_ng_dies_id.dies_id
left join m_dies
  on after_press_dies.dies_id = m_dies.id
left join m_pressing_type
  on after_press_dies.pressing_type_id = m_pressing_type.id
order by date_format(after_press_dies.press_date_at, '%y%m%d') desc, m_dies.die_number asc
;
```

こうなるか。。。これで、漸く、V3 の改造の話が出来るようになった。しっかし、このキーボードダメだな。。中途半端に 60%キーボードとか作ると、機能が不足して、成立していない。V3 は窒化の全金型検索の所が動かない問題から。。。。ちゃんと動くね、、、ただ、並び替えが型順になっているのか？並び替えも、ok。次は、窒化の条件。型の大きさによっても変わる。

窒化後の金型の閾値による色付けも完了。
次は、V3 の作成の継続。
表の読み出しは、出来るようになった。次は、1 段目のアクティベート。
アクティベートと、その他の要素のアクティベート、ディスアクティベートも OK。次は、検索してみるか。

検索は出来るようになったが、あいまい検索できるようにしたい。任意の一文字は.で示すらしい。検索は出来るようになった。

Action にソート機能を追加。active でソートすると、どうせ戻したくなる。戻すには日付順なのだが、日付が、年が入っていないため、その年内だけの比較になってしまう。年をまたぐことも出来ない。という事で、これは、年も入った日付で戻すカラムが必要になる。

やったけど、完全には同じにならない。ソートの順番が違う。。。まあこのぐらいは許してもらうか。。。

次は、WashingTankList のフィルター。フィルター類、1 段目は完了。

次は、保存。
ようやく、washing モードの保存だけできた。
クリックしてセレクトできるのは、一つの表だけにしないとね。出来るようになった。

戻す方も出来るようになったが、色を付けてやらないと、全然分からない。色付けまで、完了。
次は、ラッキングのテーブルとのやり取り。
ラッキングテーブルの戻し側が色が塗られない。
戻し側も色塗りが出来るようになった。1 段目は完成かな。。。

`washing tank table`のソート問題なし。`racking tank table`のソートも実装。次は、右ボタンのアクティベート。

ハイライト出来るようになった。
2 段目まで完成か。
3 段目、ボタンのアクティベーションがめんどくさい。アクティベーション完了。次はファイルのアップロード。これは、コピペで問題なし次は、4 つ以上の写真が表現できるようにする。アップロードした写真の処理完了。次は、Save ボタンのアクティベーション。

`save`の動作は出来たが、アクティベーションが良くない。修正完了。次は、読み出しの方。写真の読み出しは出来るようになった。でも、データがあるのと、ないのとの違いが分からない。

`save`のアクティベーションがまだ問題。処理内容を選んでいなくても、アクティブになってしまう。それと、フィルターが効かない。どちらも OK。

次は、金型別の押出を含めた履歴の表示。画像を保存しているかどうかの表示も出したい。
画像の有無の表示は出来るようになった。でも、これ保存するとき、修正と言うか、更新が出来ないですね。。。これは次のバージョンの時に考えましょうか。。。

次は金型の履歴をクリックした時に画像を表示する。

金型履歴をクリックすると、一つ上の選択した写真が見えなくなる。そして、二度と表示しなくなる。

問題解決。次は表示している写真を大きく見せる所。

次のテーマか？どのように見せるか、それも大事だね。

![](./img/20250704_img.jpg)

品番のこの画面、カテゴリー変更が非常にやりにくい。変更後、変更先に移動するのは止めた方がいい。
カテゴリーを追加したりしたとき、その内容が更新されない。ページリロードが必要になる。カテゴリーが多いので、検索機能が必要。PN でのフィルタリングがやりにくい。下部の表も必要ないものも表示している感じがある。
![](./img/20250704_01.jpg)

# 2025/08/02

久しぶりだから、まったくわかっていない。

もし、一回も、洗浄や、ラッキングの記録が無い金型をプレスした場合、リストに載るか？

前回の洗浄や、ラッキングの後に押し出した金型の表が欲しいが、新しい金型の場合、前回の記録はない。だから表示されなくなる。これは、どの金型にも洗浄記録がある事が前提になっているため。

つまり、最新の、洗浄、ラッキングの記録の無い金型に対しては、架空の日付が必要になる。

これは、結合の際に、もし洗浄情報が無い場合も、リストに入れるようにして解決。

# 2025/08/03

新しく送り込む金型のデータを、Web に登録できないか？
その前に、品番の登録をしないと、金型の登録が出来ない様に、しないと。
という事で、品番の登録が必要。
品番の登録の際に、カテゴリーを選ぶ必要がある。

どの品番を登録する必要があるのか、リストを元に
登録する仮のテーブルを作る。

そのテーブルを、m_production＿number にコピーする
