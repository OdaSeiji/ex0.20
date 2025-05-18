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
  var fileName = "./php/DieMaitenance/SelAfterPressDie.php";
  var sendData = {
    machine: "Dummy",
  };
  const today = new Date();
  const formattedDate = today.toISOString().slice(0, 10); // YYYY-MM-DD形式

  myAjax.myAjax(fileName, sendData);
  fillTableBody(ajaxReturnData, $("#after_press_dies__table tbody"));

  fileName = "./php/DieMaitenance/SelWashingDie.php";
  myAjax.myAjax(fileName, sendData);
  fillTableBody(ajaxReturnData, $("#washing_dies__table tbody"));

  $("#washing_date__input").val(formattedDate);
  // fillTestTable(100);
});

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

$("#tank_number__select").on("change", function () {
  checkWashingCondition();
});

$("#washing_date__input").on("change", function () {
  checkWashingCondition();
});

$(document).on("change", "#tank_number__select", function () {
  if ($(this).val() === "0") {
    $(this).addClass("required-input");
  } else {
    $(this).removeClass("required-input");
  }
});

function checkWashingCondition() {
  const selectDieObj = $(
    "#after_press_dies__table tr.selected-record td:nth-child(1)"
  );
  const washingDate = $("#washing_date__input").val();
  const washingTank = $("#tank_number__select").val();

  // console.log(selectDieObj);
  // console.log(washingDate);
  // console.log(washingTank);

  if (selectDieObj.length != 0 && washingTank != 0) {
    console.log("Insert");
    $("#washing__button").prop("disabled", false);
  }
}

$("#washing__button").on("click", function () {
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
