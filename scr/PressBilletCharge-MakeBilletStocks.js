var data = localStorage.getItem("sharedData");

var billetSize = 0;
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
  billetSize = event.data;
  console.log(billetSize);
});

$(document).on("click", "#stock-add__button", function () {
  //
  const editRow = $("#billet-stocks__table tbody tr.input-record");
  // const billetSize = 12;
  const billetLength = 1200;
  const emptyRow = `
    <tr class="input-record">
        <td><input type="text" name="id"></td>
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
        <td><input type="text" name="lotNumber"></td>
    </tr>
  `;

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
  if (row.find("select").val() == 0) {
    row.addClass("input-required");
  } else {
    row.removeClass("input-required");
  }

  // if(row.find("td").eq)
  console.log(row.find("td").eq(3).find("input"));
  console.log(row.find("td").eq(3).find("input").val());

  const billetQty = row.find("td").eq(3).find("input").val();
  if (Number(billetQty) < 1 && Number(billetQty) > 50) {
    row.addClass("input-required");
  } else {
    row.removeClass("input-required");
  }
}
