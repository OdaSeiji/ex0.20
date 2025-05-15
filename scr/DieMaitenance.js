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
  const fileName = "./php/DieMaitenance/SelAfterPressDie.php";
  const sendData = {
    machine: "Dummy",
  };
  const today = new Date();
  const formattedDate = today.toISOString().slice(0, 10); // YYYY-MM-DD形式

  myAjax.myAjax(fileName, sendData);
  fillTableBody(ajaxReturnData, $("#after_press_dies__table tbody"));

  $("#washing_date__input").val(formattedDate);
});

$(document).on("click", "table tbody tr", function () {
  $(this).toggleClass("selected-record");
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
