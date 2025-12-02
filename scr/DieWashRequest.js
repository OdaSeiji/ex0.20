// 2025/10/26 made
let summaryTable = new Object();
let tableFilterConfig = {
  targetTableBody: null,
  targetTableContent: null,
  targetColumnName: "",
  filterText: "",
};

const codeNumber = Math.floor(Math.random() * 90) + 10; // 10〜99

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

  console.log(codeNumber);

  $("#washing_date__input").val(formattedDate);

  makeStaffSelect();
  makeDiesSelect();
  makeApprovalTalbe();

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
  $("#modal").show();
});

$(document).on("click", "#modal-close__button", function () {
  $("#modal").fadeOut();
});

$(document).on("click", "#modal-resister__button", function () {
  const fileName = "./php/DieWashRequest/InsWashingRequest.php";
  const sendData = collectFormData();
  const today = new Date();
  const formattedDate = today.toISOString().slice(0, 10); // YYYY-MM-DD形式

  myAjax.myAjax(fileName, sendData);

  $("#applicant__wrapper select").val(0).css("background-color", "#ffddae");
  $("#washing_date__input").val(formattedDate);
  $("#reson-for-washing__textarea").val("").css("background-color", "#ffddae");

  makeApprovalTalbe();

  $("#modal").fadeOut();
});

$(document).on("click", "#test__button", function () {
  makeApprovalTalbe();
});

function makeApprovalTalbe() {
  var fileName = "./php/DieWashRequest/SelApplicaitonList.php";
  var sendData = {
    machine: "Dummy",
  };

  myAjax.myAjax(fileName, sendData);
  summaryTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#approval__table tbody"));
}

function makeAfterPressTalbe() {
  var fileName = "./php/DieMaitenance/SelAfterPressDie.php";
  var sendData = {
    machine: "Dummy",
  };

  myAjax.myAjax(fileName, sendData);
  summaryTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#after_press_dies__table tbody"));
}

$(document).on("click", "#approval__table tbody tr", function () {
  $(this).toggleClass("selected-record");
  const selectedCount = $("#approval__table tbody tr.selected-record").length;
  console.log("hello: ", selectedCount);
  console.log(selectedCount);

  // $(".approval-button__wrapper button").prop("disabled", "false");
  if (selectedCount == 0) {
    $(".approval-button__wrapper button").prop("disabled", true);
  } else {
    $(".approval-button__wrapper button").prop("disabled", false);
  }
});

$(document).ready(function () {
  $(document).on("click", "#approval-close__button", function () {
    $("#approval__modal").fadeOut();
  });
});

$(document).on("keyup", "#no1_letter", function () {
  const flag = checkCode1(Number($(this).val()));
  $("#no2_letter").focus();
  if (flag) {
    $(this).css("border", "1px solid #377a94");
  } else {
    $(this).css("border", "2px solid red");
  }
});

$(document).on("keydown", "#no1_letter", function () {});

function checkCode1(value) {
  const firstDigit = Math.floor(codeNumber / 10);
  if (firstDigit === value) {
    return true;
  } else {
    return false;
  }
}

$(document).on("keyup", "#no2_letter", function () {
  const flag = checkCode2(Number($(this).val()));
  if (flag) {
    $(this).css("border", "1px solid #377a94");
  } else {
    $(this).css("border", "2px solid red");
  }

  if (areBothBordersMatched()) {
    $("#m-ok__button").prop("disabled", false);
  } else {
    $("#m-ok__button").prop("disabled", true);
  }
  $("#approval-close__button").focus();
});

$(document).on("keydown", "#no2_letter", function () {});

function checkCode2(value) {
  const firstDigit = Math.floor(codeNumber % 10);
  if (firstDigit === value) {
    return true;
  } else {
    return false;
  }
}

function areBothBordersMatched() {
  const targetColor = "rgb(55, 122, 148)"; // #377a94 のRGB表現
  const input1Color = $("#no1_letter").css("border-color");
  const input2Color = $("#no2_letter").css("border-color");
  return input1Color === targetColor && input2Color === targetColor;
}

$(document).on("focus", "#no1_letter, #no2_letter", function () {
  // console.log("hello");
  $(this).val("");
});
