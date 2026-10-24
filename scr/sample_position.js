/* サンプル位置の名前（硬度・粗さ・エッチングの測定記録で共通）
   exd11 の HardNessV3 / MeasurementV2 / EtchingV5 と同じ決まり。nBn（n 本のビレットで m 本の製品）の n で位置の並びが決まる：
     1B: H・E、2B: H・A・B・E、3B: H・A・B・C・D・E、4B: H・A〜F・END、5B: H・A〜F・I・K・END
   先頭の数字は何番目の並びか（1H, 1E, 2H, 2E…）。行の数は ビレット本数 × 2 × m × 穴数（ex0.11 と同じ） */

// i 番目（0 から）の位置の名前
function samplePositionLabel(i, n) {
  const pairs = ["A", "B", "C", "D", "E", "F", "I", "K"].slice(0, 2 * (n - 1));
  const cycle = ["H", ...pairs, n <= 3 ? "E" : "END"];
  return `${Math.floor(i / (2 * n)) + 1}${cycle[i % (2 * n)]}`;
}

// 押出の位置の名前の一覧。nBn（"2B1" など）・ビレット本数・穴数のどれかが無ければ空
function samplePositions(nbn, billets, hole) {
  const nm = /^(\d+)B(\d+)$/.exec(nbn || "");
  if (!nm || !billets || !hole) return [];
  const n = Math.min(Number(nm[1]), 5);
  const m = Number(nm[2]);
  const count = Math.ceil(billets * 2 * m * hole);
  return Array.from({ length: count }, (_, i) => samplePositionLabel(i, n));
}

/* 粗さ（MeasurementV2）は 1B・2B だけ並びが違い、中間（M）も測る。行の数も ビレット本数 × 3 × m × 穴数
     1B: H・M・E、2B: H・M・A・B・M1・E、3B 以上は上と同じ */
function roughnessPositionLabel(i, n) {
  if (n > 2) return samplePositionLabel(i, n);
  const cycle = n === 1 ? ["H", "M", "E"] : ["H", "M", "A", "B", "M1", "E"];
  return `${Math.floor(i / cycle.length) + 1}${cycle[i % cycle.length]}`;
}

function roughnessPositions(nbn, billets, hole) {
  const nm = /^(\d+)B(\d+)$/.exec(nbn || "");
  if (!nm || !billets || !hole) return [];
  const n = Math.min(Number(nm[1]), 5);
  const m = Number(nm[2]);
  const count = Math.ceil(billets * (n <= 2 ? 3 : 2) * m * hole);
  return Array.from({ length: count }, (_, i) => roughnessPositionLabel(i, n));
}
