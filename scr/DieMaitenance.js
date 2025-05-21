var summaryTable = new Object();
var ajaxReturnData;

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
  makeStaffSelectSelect();
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

  makeStaffSelectSelect();
}

function fillTestTable(dies_id) {
  // var dies_id = 100;
  var fileName = "./php/DieMaitenance/SelDieStatus.php";
  var sendData = {
    dies_id: dies_id,
  };
  myAjax.myAjax(fileName, sendData);
  fillTableBody(ajaxReturnData, $("#test__table tbody"));
}

function fillDieHistoryTable(dies_id) {
  var fileName = "./php/DieMaitenance/SelDiePressHistory.php";
  var sendData = {
    dies_id: dies_id,
  };
  myAjax.myAjax(fileName, sendData);
  fillTableBody(ajaxReturnData, $("#die-history__table tbody"));
}

$(document).on("click", "table#after_press_dies__table tbody tr", function () {
  $(this).toggleClass("selected-record");
  checkWashingCondition();
});

$(document).on("click", "#after_press_dies__table tbody tr", function () {
  console.log("hello");
  var targetObj;
  targetObj = $(this).find("td:first");
  console.log(targetObj.text());
  fillTestTable(targetObj.text());
  fillDieHistoryTable(targetObj.text());
});

$(document).on("focus", "#edit-lotnumber__input", function () {
  console.log("hello");

  const fileName = "./php/DieMaitenance/SelNoMantenanceDie.php";
  const sendData = {
    machine: "Dummy",
  };
  // console.log(number);
  myAjax.myAjax(fileName, sendData);
});

$(document).on("change", "select", function () {
  if ($(this).val() === "0") {
    $(this).addClass("required-input");
  } else {
    $(this).removeClass("required-input");
  }
});

$(".after_press_dies select").on("change", function () {
  checkWashingCondition();
});

$("#washing_date__input").on("change", function () {
  checkWashingCondition();
});

function checkWashingCondition() {
  const selectDieObj = $(
    "#after_press_dies__table tr.selected-record td:nth-child(1)"
  );
  const washingDate = $("#washing_date__input").val();
  const washingTank = $("#tank_number__select").val();
  const staffId = $("#staff__select").val();

  console.log("Hello");

  // console.log(selectDieObj);
  // console.log(washingDate);
  // console.log(washingTank);

  if (selectDieObj.length != 0 && washingTank != 0 && staffId != 0) {
    // $("#washing__button").prop("disabled", false);
  }
}

$("#washing__button").on("click", function () {
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
    // const row = [];
    data.push([
      $(this).html(),
      currentDayteTime,
      tankNumber,
      currentDayte,
      staffId,
    ]);
  });
  console.log(data);
  console.log(JSON.stringify(data));

  const fileName = "./php/DieMaitenance/InsWashingDie.php";
  const sendData = {
    tableData: JSON.stringify(data),
  };
  myAjax.myAjax(fileName, sendData);

  makeAfterPressTalbe();
  makeWashingDieTable();

  $("#washing__button").prop("disabled", true);
});

$(document).on("keyup", "#die-number-sort__text", function () {
  $(this).val($(this).val().toUpperCase()); // 小文字を大文字に
  const text = $(this).val();

  $("#after_press_dies__table tbody").empty();

  // console.log(summaryTable);

  // return;

  summaryTable.forEach(function (trVal) {
    if (trVal["die_number"].startsWith(text)) {
      var newTr = $("<tr>");
      Object.keys(trVal).forEach(function (tdVal) {
        $("<td>").html(trVal[tdVal]).appendTo(newTr);
      });
      $(newTr).appendTo("#after_press_dies__table tbody");
    }
  });

  // $("#summary__table_record").html(
  //   $("#summary__table tbody tr").length + " items"
  // );
});

function makeStaffSelectSelect() {
  fileName = "./php/DieMaitenance/SelStaffList.php";
  sendData = {
    dieName: "%",
  };
  myAjax.myAjax(fileName, sendData);

  ajaxReturnData.forEach(function (value) {
    $("<option>")
      .val(value["id"])
      .html(value["staff_name"])
      .appendTo("#staff__select");
  });
}
