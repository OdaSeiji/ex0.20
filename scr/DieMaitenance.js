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
  // 実行したい処理をここに書きます
  console.log("ドキュメントがロードされました！");

  const fileName = "./php/DieMaitenance/SelAfterPressDie.php";
  // const fileName = "./php/DieMaitenance/test.php";
  const sendData = {
    machine: "Dummy",
  };
  // console.log(number);
  myAjax.myAjax(fileName, sendData);
  fillTableBody(ajaxReturnData, $("#after_press_dies__table tbody"));
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
