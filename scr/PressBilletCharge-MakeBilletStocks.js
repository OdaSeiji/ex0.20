var data = localStorage.getItem("sharedData");

var billetSize = 0;
var billetMaterialId = 0;
var machineNumber = 0;

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

window.addEventListener("message", (event) => {
  console.log(event.data); // "こんにちは"
  billetSize = event.data.billetSize;
  billetMaterialId = event.data.billetMaterialId;
  console.log(billetSize);
  console.log(billetMaterialId);
});

$(document).on("click", "#stock-add__button", function () {
  //
  const editRow = $("#billet-stocks__table tbody tr.input-record");
  // const billetSize = 12;
  const billetLength = 1200;
  const emptyRow = `
    <tr class="input-record">
        <td></td>
        <td>
        <select>
          <option value=0>-</option>
          <option value=1>SMC</option>
          <option value=2>Dubai</option>
        </select>
        </td>
        <td>
          ${billetSize}
        </td>
        <td><input type="text" name="qty"></td>
        <td><input type="text" name="length" value="${billetLength}"></td>
        <td><input id="edit-lotnumber__input" type="text" name="lotNumber"></td>
    </tr>
  `;

  $("#edit-lotnumber__input").removeAttr("id");
  editRow.removeClass("input-record");
  editRow.find("input").attr("readonly", true);
  // editRow.find("input").attr("pointer-events", none);
  $("#billet-stocks__table tbody").append(emptyRow);
});

$(document).on("click", "#window-close__img", function () {
  window.close();
  window.open("./PressBilletCharge-SelectMachine.html", "_blank"); // 新しいHTMLファイルを開く
});

$(document).on("blur", "#billet-stocks__table tbody tr", function () {
  inputValidation($(this));
});

function inputValidation(row) {
  const value = Number(row.find("input").eq(0).val());
  // vendor check
  if (row.find("select").val() == 0) {
    row.addClass("input-required");
    console.log("billet Vendor ng");
  } else {
    row.removeClass("input-required");
  }
  // Qty check
  if (!Number.isInteger(value) || value <= 0) {
    row.addClass("input-required");
    console.log("billet qty ng");
  } else {
    row.removeClass("input-required");
  }
}

$(document).on("focus", "#edit-lotnumber__input", function () {
  console.log("hello");

  const fileName = "./php/billet-charge/SelBilletLotNumber.php";
  const sendData = {
    machine: "Dummy",
  };
  // console.log(number);
  myAjax.myAjax(fileName, sendData);
});

$(document).on("click", "#select-billet__button", function () {
  console.log("hello");
  const targetTable = $("#billet-stocks-lotnumber__table tbody");
  const fileName = "./php/billet-charge/SelBilletLotNumber.php";
  const sendData = {
    machine: "Dummy",
  };
  // console.log(number);
  myAjax.myAjax(fileName, sendData);

  targetTable.empty();
  ajaxReturnData.forEach(function (trVal) {
    var newTr = $("<tr>");
    Object.keys(trVal).forEach(function (tdVal) {
      $("<td>").html(trVal[tdVal]).appendTo(newTr);
    });
    $(newTr).appendTo(targetTable);
  });
});
