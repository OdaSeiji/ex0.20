// 初期値
let ajaxReturnData;
let cancelKeyupEvent = false;
let cancelKeydownEvent = false;
let editMode = false;
let readNewFile = false;

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

// press date
$(document).on("click", "#billet-charge__button", function () {
  // window.parent.postMessage("click__billet-charge", "*");
  // window.parent.postMessage("./PressBilletCharge-SelectMachine.html", "*");
  window.open("./PressBilletCharge-SelectMachine.html");
});

// die maitenance
$(document).on("click", "#die-maitenance__button", function () {
  window.open("./DieMaitenance.html");
  // console.log("hello");
});
