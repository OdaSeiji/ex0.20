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

  makeAfterPressTalbe();
  makeWashingDieTable();
  makeWashingStaffSelectSelect();
  $("#washing_date__input").val(formattedDate);
});

function makeAfterPressTalbe() {
  var fileName = "./php/DieMaitenance/SelAfterPressDie.php";
  var sendData = {
    machine: "Dummy",
  };
  const today = new Date();
  const formattedDate = today.toISOString().slice(0, 10); // YYYY-MM-DD形式

  myAjax.myAjax(fileName, sendData);
  summaryTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#after_press_dies__table tbody"));
}

function makeWashingDieTable() {
  var fileName = "./php/DieMaitenance/SelWashingDie.php";
  var sendData = {
    machine: "Dummy",
  };
  myAjax.myAjax(fileName, sendData);
  fillTableBody(ajaxReturnData, $("#washing_dies__table tbody"));
  washingDieTable = ajaxReturnData;
}

function makeWashingStaffSelectSelect() {
  const fileName = "./php/DieMaitenance/SelStaffList.php";
  const sendData = {
    staffOrder: staffOrderMode,
  };
  myAjax.myAjax(fileName, sendData);
  $("#wash-staff__select").empty();
  $("#wash-staff__select").append($("<option>").html("-").val(0));

  ajaxReturnData.forEach(function (value) {
    $("<option>")
      .val(value["id"])
      .html(value["staff_name"])
      .appendTo("#wash-staff__select");
  });
}

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

          break;
        case "washing_dies__table": // washing mode
          $("#washing_dies__table").removeClass("inactive__table");
          $("#racking_dies__table").addClass("inactive__table");
          $("caption.racking-dies__caption").addClass("inactive__caption");
          $("caption.washing-dies__caption").removeClass("inactive__caption");
          $("#washing__div").removeClass("inactive__div");
          $("#racking__div").addClass("inactive__div");
          break;
      }
    }
  }
);

$(document).on(
  "change",
  "div.sub__container:not(.inactive__div) select",
  function () {
    let targetObj = $(this);
    console.log("Hello");
    if ($(this).val() == 0) {
      targetObj.addClass("required-input");
    } else {
      targetObj.removeClass("required-input");
    }
  }
);

$(document).on("click", "tbody tr", function () {
  $(this).toggleClass("selected-record");
});

$("#right-arrow__img").on("click", function () {
  const now = new Date();
  const hours = now.getHours();
  const minutes = now.getMinutes();
  const seconds = now.getSeconds();
  const currentTime = `${hours}:${minutes}:${seconds}`;
  const currentDayteTime = $("#washing_date__input").val() + " " + currentTime;
  const currentDayte = $("#washing_date__input").val();
  const tankNumber = $("#tank_number__select").val();
  const staffId = $("#staff__select").val();
  const data = [];
  var dieIdObj;

  dieIdObj = $("#after_press_dies__table tr.selected-record td:nth-child(1)");

  dieIdObj.each(function () {
    data.push([
      $(this).html(),
      currentDayteTime,
      tankNumber,
      currentDayte,
      staffId,
    ]);
  });

  const fileName = "./php/DieMaitenance/InsWashingDie.php";
  const sendData = {
    tableData: JSON.stringify(data),
  };
  myAjax.myAjax(fileName, sendData);

  makeAfterPressTalbe();
  makeWashingDieTable();

  // return;
  // color the selected dies name
  $("#washing_dies__table tbody tr").each(function () {
    const cellText = $(this).find("td").eq(0).text();
    const targetTr = $(this);
    dieIdObj.each(function () {
      if (cellText === $(this).html()) {
        targetTr.css("background-color", "#fffaad");
      }
    });
    // reset input values
    $("#tank_number__select").val(0).addClass("required-input");
    $("#staff__select").val(0).addClass("required-input");
  });
  $(this).prop("disabled", true);
});

$(document).on("click, change", "#washing__div", function () {
  checkWashingCondition();
});

$(document).on("click", "#after_press_dies__table", function () {
  console.log("Hello");
  checkWashingCondition();
});

function checkWashingCondition() {
  let inputValidation = true;
  // Input Validation
  $("#washing__div .save-data").each(function () {
    if ($(this).val() == 0) {
      inputValidation = false;
    }
  });

  if ($("#after_press_dies__table .selected-record").length == 0) {
    inputValidation = false;
  }

  if (inputValidation) {
    $("#right-arrow__img").attr("src", "./img/right_arrow-3.png");
  } else {
    $("#right-arrow__img").attr("src", "./img/right_arrow-4.png");
  }
}

$("#test__button").on("click", function () {
  checkWashingCondition();
});
