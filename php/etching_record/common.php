<?php
require_once __DIR__ . "/../sample_record/common.php";

// エッチング評価記録で共通に使う値（exd11 EtchingV5 の画面に直書きされていたもの）
//   判定 jug：1 = OK, 2 = NG, 3 = 空欄, 5 = BH（4 = Rút は ex0.11 でも選べないので使わない）
//   不良コード code：m_quality_code.id。11 = 311, 19 = 319, 20 = 320, 25 = 351, 33 = コードなし
//   状態 etching_finish（t_press_sub）：0 = 未完了, 1 = 完了, 2 = 製品を捨てた, 3 = エッチングしない, 4 = エッチング中
const ER_JUGS     = [1, 2, 3, 5];
const ER_CODES    = [11, 19, 20, 25, 33];
const ER_NO_CODE  = 33;
const ER_FINISHES = [0, 1, 2, 3, 4];
const ER_ROUND_COLS = [
    ["`1st_jug`", "`1st_code`"], ["`2nd_jug`", "`2nd_code`"], ["`3rd_jug`", "`3rd_code`"],
    ["`4th_jug`", "`4th_code`"], ["`5th_jug`", "`5th_code`"],
];

// 写真の置き場（ex0.11 と同じ htdocs/upload/Etching。画面からは ../upload/Etching/ファイル名）
const ER_PHOTO_DIR = __DIR__ . "/../../../upload/Etching";
