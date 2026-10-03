# 2026-10-03 使われていない表 m_checkbillet を削除

## 目的

ビレット棚卸し（exd11 CheckBilletV4 → ex0.20 へ移植予定）を調べたところ、`t_checkbillet` と同じような形の空の表 `m_checkbillet` が見つかった。
どこからも使われていないので削除する。

## 確認したこと

- ex0.11（exd11）・ex0.20・ex0.21 のコード（php / js / html / py）に `m_checkbillet` という名前は出てこない
- ローカルは 0 件
- 列名は `A6061-228-600` のようにハイフン入りで、`t_checkbillet` の作りかけの表と思われる

## DB変更

| SQL | ローカル | 本番 |
|---|---|---|
| `m_checkbillet` の削除 | 実行済み（2026-10-03） | 実行済み（ユーザーが以前に削除済み。2026-10-03 に確認） |

本番で実行する前に、件数が 0 であることを確かめる。

```sql
SELECT COUNT(*) FROM m_checkbillet;   -- 0 なら削除してよい
DROP TABLE m_checkbillet;
```

確認用：
```sql
SHOW TABLES LIKE '%checkbillet%';   -- t_checkbillet だけになる
```

※ 本番を消さないと、次に本番 PC から DB を取り寄せたときにこの表がローカルに戻ってくる。
