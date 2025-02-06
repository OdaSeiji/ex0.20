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
  const fileName = "./php/billet-charge/SelPressDirective.php";
  const sendData = {
    dummy: "dummy",
  };

  myAjax.myAjax(fileName, sendData);

  $("#summary__table tbody").empty();
  ajaxReturnData.forEach(function (trVal) {
    var newTr = $("<tr>");
    Object.keys(trVal).forEach(function (tdVal) {
      $("<td>").html(trVal[tdVal]).appendTo(newTr);
    });
    $(newTr).appendTo("#summary__table tbody");
  });
});

$(document).on("click", "#test__button", function () {
  const fileName = "./php/billet-charge/SelPressDirective.php";
  const sendData = {
    dummy: "dummy",
  };

  myAjax.myAjax(fileName, sendData);
  console.log(ajaxReturnData);

  $("#summary__table tbody").empty();
  ajaxReturnData.forEach(function (trVal) {
    var newTr = $("<tr>");
    Object.keys(trVal).forEach(function (tdVal) {
      $("<td>").html(trVal[tdVal]).appendTo(newTr);
    });
    $(newTr).appendTo("#summary__table tbody");
  });
});

$(document).on("click", "#summary__table tbody tr", function () {
  $(this).parent().find("tr").removeClass("selected-record");
  $(this).addClass("selected-record");
});

$(document).on("click", "#stock-add__button", function () {
  //
  const editRow = $("#stock-billet__table tbody tr.input-record");
  const summaryRow = $("#summary__table tr.selected-record");
  const billetSize = summaryRow.find("td:eq(6)").html();
  const billetLength = summaryRow.find("td:eq(7)").html();
  const emptyRow = `
    <tr class="input-record">
        <td><input type="text" name="id"></td>
        <td><input type="text" name="size" value="${billetSize}"></td>
        <td><input type="text" name="length" value="${billetLength}"></td>
        <td><input type="text" name="vendor"></td>
        <td><input type="text" name="qty"></td>
    </tr>
  `;

  editRow.removeClass("input-record");
  editRow.find("input").attr("readonly", true);
  // editRow.find("input").attr("pointer-events", none);
  $("#stock-billet__table tbody").append(emptyRow);

  console.log();
});
