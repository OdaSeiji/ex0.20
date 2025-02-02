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

$(window).on("message", function (event) {
  const messageLetters = event.originalEvent.data;
  // 受信したデータを処理します
  console.log("受信したデータ:", event.originalEvent.data);
  if (messageLetters == "click__billet-charge") {
    console.log("Get");
    $("iframe").attr("src", "./press_billet-charge.html");
    $("#header-title__div").html("Billet Charge");
  }
});

$(document).on("click", "#window-close__img", function () {
  console.log("close button");
  $("iframe").attr("src", "./toppage.html");
  $("#header-title__div").html("Extrusion Page");
});
