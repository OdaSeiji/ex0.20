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
  fillTestTable(100);
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

$(document).on("click", "table tbody tr", function () {
  $(this).toggleClass("selected-record");
});

$(document).on("click", "#after_press_dies__table tbody tr", function () {
  console.log("hello");
  var targetObj;
  targetObj = $(this).find("td:first");
  console.log(targetObj.text());
  fillTestTable(targetObj.text());
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

$("#after_press_dies__table tbody").on("click", function () {
  checkWashingCondition();
});

$("#tank_number__select").on("change", function () {
  checkWashingCondition();
});

$("#washing_date__input").on("change", function () {
  checkWashingCondition();
});

function checkWashingCondition() {
  console.log("hello");
}

$("#wash_die__img").on("click", function () {
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
