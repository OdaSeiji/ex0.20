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
  var emptyRow = `
    <tr class="input-record">
        <td><input type="text" name="id"></td>
        <td><input type="text" name="size"></td>
        <td><input type="text" name="length"></td>
        <td><input type="text" name="vendor"></td>
        <td><input type="text" name="qty"></td>
    </tr>
  `;
  $("#stock-billet__table tbody").append(emptyRow);
});
