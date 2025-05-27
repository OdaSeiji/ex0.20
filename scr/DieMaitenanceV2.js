var summaryTable = new Object();
var washingDieTable = new Object();
var ajaxReturnData;
var staffOrderMode = 4;
let timeout;

function resetTimeout() {
  // Display the washing tank when there is no activity for a certain period of time.
  const button = document.querySelector("#wash-mode__button");
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    button.click();
    // console.log("一定時間操作がありませんでした！");
  }, 30000 * 5); // 30 * 5 秒（5 * 30000ミリ秒）後に動作する
}

// イベントリスナーを設定
// window.addEventListener("mousemove", resetTimeout);
window.addEventListener("keydown", resetTimeout);
window.addEventListener("click", resetTimeout);

// 初期化
resetTimeout();

const myAjax = {
  myAjax: function (fileName, sendData) {
    $.ajax({
      type: "POST",
      url: fileName,
      dataType: "json",
      data: sendData,
      async: false,
    })
      .done(function (data) {
        ajaxReturnData = data;
      })
      .fail(function () {
        alert("DB connect error");
      });
  },
};

$(document).on(
  "click",
  "table#washing_dies__table th, table#racking_dies__table th",
  function () {
    console.log("Hello");
  }
);
