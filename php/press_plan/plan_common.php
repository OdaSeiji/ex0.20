<?php
// 計画1行の値を確かめて、t_press_plan に入れる形にする。問題があれば文字列（エラーの説明）を返す
const PLAN_SHIFTS = [4, 1, 2, 3];   // 4 = HC
// 計画の削除のパスコード（テスト運用のため。ex0.21 のログインができたら、削除できる人で制限する）
const PLAN_DELETE_PASSCODE = "1031";

function normalizePlanRow(array $r)
{
    $diesId = (int)($r["dies_id"] ?? 0);
    $pnId   = (int)($r["production_number_id"] ?? 0);
    $shift  = (int)($r["shift_id"] ?? 0);
    $qty    = (string)($r["quantity"] ?? "");
    $ord    = trim((string)($r["ordinal"] ?? ""));
    if ($diesId <= 0 || $pnId <= 0) return "金型・品番がありません";
    if (!in_array($shift, PLAN_SHIFTS, true)) return "直が正しくありません";
    if (!preg_match('/^\d+$/', $qty)) return "本数は 0 以上の整数で入れてください";
    if (mb_strlen($ord) > 11) return "順番が長すぎます";
    return [
        "dies_id"              => $diesId,
        "production_number_id" => $pnId,
        "shift_id"             => $shift,
        "ordinal"              => $ord === "" ? null : $ord,
        "quantity"             => (int)$qty,
        "nitride_use"          => ((int)($r["nitride_use"] ?? 0)) === 1 ? 1 : 0,
        "note"                 => mb_substr(trim((string)($r["note"] ?? "")), 0, 400),
    ];
}

function checkDateMachine($date, $machine)
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date)) return "押出日を選んでください";
    if (!in_array((int)$machine, [1, 2, 3, 4], true)) return "号機を選んでください";
    return null;
}
