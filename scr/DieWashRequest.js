// 2025/10/26 made
let summaryTable = new Object();
let tableFilterConfig = {
  targetTableBody: null,
  targetTableContent: null,
  targetColumnName: "",
  filterText: "",
};

function resetTimeout() {
  // Display the washing tank when there is no activity for a certain period of time.
  // const button = document.querySelector("#wash-mode__button");
  // clearTimeout(timeout);
  // timeout = setTimeout(() => {
  //   button.click();
  //   // console.log("一定時間操作がありませんでした！");
  // }, 30000 * 5); // 30 * 5 秒（5 * 30000ミリ秒）後に動作する
}

// イベントリスナーを設定
// window.addEventListener("mousemove", resetTimeout);
window.addEventListener("keydown", resetTimeout);
window.addEventListener("click", resetTimeout);

// 初期化
resetTimeout();

// 2 min after load excute
window.onload = function () {
  setTimeout(function () {
    // makeRackingTable();
    // makeFixDieList();
    // makeFixStaffSelectSelect();
    // makeAllDiesStatusTable();
    // makeRackingStaffSelect();
  }, 1000); // 1000ミリ秒 = 1秒
};

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

function fillTableBody(data, tbodyDom) {
  $(tbodyDom).empty();
  data.forEach(function (trVal) {
    let newTr = $("<tr>");
    Object.keys(trVal).forEach(function (tdVal, index) {
      $("<td>").html(trVal[tdVal]).appendTo(newTr);
    });
    $(newTr).appendTo(tbodyDom);
  });
}

$(function () {
  const today = new Date();
  const formattedDate = today.toISOString().slice(0, 10); // YYYY-MM-DD形式

  $("#washing_date__input").val(formattedDate);

  makeStaffSelect();
  makeDiesSelect();

  $("#applicant__wrapper select, #applicant__wrapper textarea").on(
    "keyup change",
    function () {
      checkBackgroundColors();
    }
  );
});

function makeStaffSelect() {
  const fileName = "./php/DieWashRequest/SelStaffList.php";
  const sendData = {
    dummy: "dummy",
  };
  myAjax.myAjax(fileName, sendData);
  $("#applicant__select")
    .empty()
    .append($("<option>").html("-").val(0).addClass("centered-option"));
  ajaxReturnData.forEach(function (value) {
    $("<option>")
      .val(value["id"])
      .html(value["staff_name"])
      .appendTo("#applicant__select");
  });
}

function makeDiesSelect() {
  const fileName = "./php/DieWashRequest/SelDiesList.php";
  const sendData = {
    dummy: "dummy",
  };
  myAjax.myAjax(fileName, sendData);
  $("#dies-name__select")
    .empty()
    .append($("<option>").html("-").val(0).addClass("centered-option"));
  ajaxReturnData.forEach(function (value) {
    $("<option>")
      .val(value["id"])
      .html(value["die_number"])
      .appendTo("#dies-name__select");
  });
}

$("select").on("change", function () {
  const val = $(this).val();
  if (val === "0") {
    $(this).css("background-color", "#ffddae");
  } else {
    $(this).css("background-color", "#ffffff");
  }
});

$("textarea").on("keyup", function () {
  const text = $(this).val();
  if (text.length >= 5) {
    $(this).css("background-color", "#ffffff"); // 白
  } else {
    $(this).css("background-color", "#ffddae"); // オレンジ
  }
});

function checkBackgroundColors() {
  let allWhite = true;

  $("#applicant__wrapper select, #applicant__wrapper textarea").each(
    function () {
      const bg = $(this).css("background-color");
      // 比較用に RGB → HEX を統一（白は rgb(255, 255, 255)）
      if (bg !== "rgb(255, 255, 255)") {
        allWhite = false;
        return false; // 1つでも違えば終了
      }
    }
  );
  $("#applicant__button").prop("disabled", !allWhite);
}

function collectFormData() {
  return {
    applicant_id: $("#applicant__select").val(),
    washing_date: $("#washing_date__input").val(),
    die_id: $("#dies-name__select").val(),
    reason: $("#reson-for-washing__textarea").val(),
  };
}

$("#applicant__button").on("click", function () {
  console.log("Hello");

  const data = collectFormData();

  $.ajax({
    url: "/submit_application.php", // バックエンドのURL
    method: "POST",
    data: data,
    success: function (response) {
      console.log("登録成功:", response);
    },
    error: function (xhr, status, error) {
      console.error("登録失敗:", error);
    },
  });
});
