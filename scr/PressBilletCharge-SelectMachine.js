let selectMachineNumber = 0;
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

$(function () {
  let pressTimer;

  $("#number1__button")
    .on("touchstart", function () {
      pressTimer = setTimeout(function () {
        $("#machine-number-info")
          .html("#1 Machine 2700MT")
          .parent()
          .addClass("imput-complete");
        selectMachineNumber = 1;
        makePressDirectiveTable(selectMachineNumber);
      }, 500);
    })
    .on("touchend touchleave touchcancel", function () {
      clearTimeout(pressTimer);
    });

  $("#number2__button")
    .on("touchstart", function () {
      pressTimer = setTimeout(function () {
        $("#machine-number-info")
          .html("#2 Machine 5000MT")
          .parent()
          .addClass("imput-complete");
        selectMachineNumber = 2;
        makePressDirectiveTable(selectMachineNumber);
      }, 500);
    })
    .on("touchend touchleave touchcancel", function () {
      clearTimeout(pressTimer);
    });

  $("#number3__button")
    .on("touchstart", function () {
      pressTimer = setTimeout(function () {
        $("#machine-number-info")
          .html("#3 Machine 2700MT")
          .parent()
          .addClass("imput-complete");
        selectMachineNumber = 3;
        makePressDirectiveTable(selectMachineNumber);
      }, 500);
    })
    .on("touchend touchleave touchcancel", function () {
      clearTimeout(pressTimer);
    });
});

function makePressDirectiveTable(number) {
  const fileName = "./php/billet-charge/SelPressDirective.php";
  const sendData = {
    machine: number,
  };
  console.log(number);
  myAjax.myAjax(fileName, sendData);

  $("#press-directive__table tbody").empty();
  ajaxReturnData.forEach(function (trVal) {
    var newTr = $("<tr>");
    Object.keys(trVal).forEach(function (tdVal) {
      $("<td>").html(trVal[tdVal]).appendTo(newTr);
    });
    $(newTr).appendTo("#press-directive__table tbody");
  });
}

$(document).on("click", "#press-directive__table tbody tr", function () {
  const dateString = $(this).find("td:eq(1)").html().split("-");
  const dieName = $(this).find("td:eq(2)").html();
  const billetSize = $(this).find("td:eq(6)").html();
  const planBilletQty = $(this).find("td:eq(4)").html();

  const pressDate = "Ngày " + dateString[2] + " tháng " + dateString[1];
  const displayString =
    pressDate +
    "  &nbsp;&nbsp;Die: " +
    dieName +
    "  &nbsp;&nbsp;BilletSize: " +
    billetSize +
    "  &nbsp;&nbsp;BilletQty:" +
    planBilletQty;

  $(this).parent().find("tr").removeClass("selected-record");
  $(this).addClass("selected-record");

  $("#press-directive-info").html(displayString);
});
