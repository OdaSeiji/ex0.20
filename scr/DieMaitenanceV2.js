let summaryTable = new Object();
let washingDieTable = new Object();
let rackingDieTable = new Object();
let fixDieTable = new Object();
let ajaxReturnData;
let staffOrderMode = 4;
let timeout;
let washingOrRacking = "washing";
let uploadFile = [];

function resetTimeout() {
  // Display the washing tank when there is no activity for a certain period of time.
  const button = document.querySelector("#wash-mode__button");
  clearTimeout(timeout);
  timeout = setTimeout(() => {
    button.click();
    // console.log("一定時間操作がありませんでした！");
  }, 30000 * 5); // 30 * 5 秒（5 * 30000ミリ秒）後に動作する
}

// イベントリスナーを設定
// window.addEventListener("mousemove", resetTimeout);
window.addEventListener("keydown", resetTimeout);
window.addEventListener("click", resetTimeout);

// 初期化
resetTimeout();

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
  const today = new Date();
  const formattedDate = today.toISOString().slice(0, 10); // YYYY-MM-DD形式

  makeAfterPressTalbe();
  makeWashingDieTable();
  makeWashingStaffSelectSelect();
  $("#washing_date__input").val(formattedDate);
  $("#racking_date__input").val(formattedDate);
  $("#fixing-date__input").val(formattedDate);
});

function makeAfterPressTalbe() {
  var fileName = "./php/DieMaitenance/SelAfterPressDie.php";
  var sendData = {
    machine: "Dummy",
  };
  const today = new Date();
  const formattedDate = today.toISOString().slice(0, 10); // YYYY-MM-DD形式

  myAjax.myAjax(fileName, sendData);
  summaryTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#after_press_dies__table tbody"));
}

function makeWashingDieTable() {
  var fileName = "./php/DieMaitenance/SelWashingDie.php";
  var sendData = {
    machine: "Dummy",
  };
  myAjax.myAjax(fileName, sendData);
  fillTableBody(ajaxReturnData, $("#washing_dies__table tbody"));
  washingDieTable = ajaxReturnData;
}

function makeWashingStaffSelectSelect() {
  const fileName = "./php/DieMaitenance/SelStaffList.php";
  const sendData = {
    staffOrder: staffOrderMode,
  };
  myAjax.myAjax(fileName, sendData);
  $("#wash-staff__select").empty();
  $("#wash-staff__select").append($("<option>").html("-").val(0));

  ajaxReturnData.forEach(function (value) {
    $("<option>")
      .val(value["id"])
      .html(value["staff_name"])
      .appendTo("#wash-staff__select");
  });
}

$(document).on(
  "click",
  "table#washing_dies__table thead, table#racking_dies__table thead",
  function () {
    if ($(this).parent("table").prop("class") === "inactive__table") {
      let idName;
      idName = $(this).parent("table").prop("id");
      switch (idName) {
        case "racking_dies__table": // racking mode
          washingOrRacking = "racking";
          $("#right-arrow__img").attr("src", "./img/right_arrow-4.png");
          $("#left-arrow__img").attr("src", "./img/right_arrow-4.png");

          $("#racking_dies__table").removeClass("inactive__table");
          $("#washing_dies__table").addClass("inactive__table");
          $("caption.washing-dies__caption").addClass("inactive__caption");
          $("caption.racking-dies__caption").removeClass("inactive__caption");
          $("#racking__div").removeClass("inactive__div");
          $("#washing__div").addClass("inactive__div");
          $("#washing__div select").val("0").addClass("required-input");

          $("#after_press_dies__table .selected-record").removeClass(
            "selected-record"
          );

          // make staff select
          staffOrderMode = 10;
          makeRackingStaffSelect();

          // make racking dies table
          makeRackingTable();

          break;
        case "washing_dies__table": // washing mode
          washingOrRacking = "washing";
          $("#right-arrow__img").attr("src", "./img/right_arrow-4.png");
          $("#left-arrow__img").attr("src", "./img/right_arrow-4.png");

          $("#washing_dies__table").removeClass("inactive__table");
          $("#racking_dies__table").addClass("inactive__table");
          $("caption.racking-dies__caption").addClass("inactive__caption");
          $("caption.washing-dies__caption").removeClass("inactive__caption");
          $("#washing__div").removeClass("inactive__div");
          $("#racking__div").addClass("inactive__div");

          $("#after_press_dies__table .selected-record").removeClass(
            "selected-record"
          );
          break;
      }
    }
  }
);

$(document).on(
  "change",
  "div.sub__container:not(.inactive__div) select",
  function () {
    let targetObj = $(this);
    console.log("Hello");
    if ($(this).val() == 0) {
      targetObj.addClass("required-input");
    } else {
      targetObj.removeClass("required-input");
    }
  }
);

// table record activation

$(document).on("click", "#after_press_dies__table tbody tr", function () {
  $("#washing_dies__table tbody tr.selected-record").removeClass(
    "selected-record"
  );
  $("#racking_dies__table tbody tr.selected-record").removeClass(
    "selected-record"
  );
  $(this).toggleClass("selected-record");

  selectedObj = $("#after_press_dies__table tr.selected-record");
  if (selectedObj.length != 0) {
    $("#right-arrow__img").attr("src", "./img/right_arrow-3.png");
    // inactive input value
    // $("#washing__div select").val("0").addClass("required-input");
    // $("#washing__div select").parent().addClass("inactive__div");
    // $("#washing_date__input").parent().addClass("inactive__div");
  } else {
    $("#right-arrow__img").attr("src", "./img/right_arrow-4.png");
    // active input value
    // $("#washing__div select").val("0").addClass("required-input");
    // $("#washing__div select").parent().removeClass("inactive__div");
    // $("#washing_date__input").parent().removeClass("inactive__div");
  }
  checkWashingCondition();
});

$(document).on("click", "#washing_dies__table tbody tr", function () {
  if ($("#washing_dies__table").hasClass("inactive__table")) {
    return;
  }

  let selectedObj;
  $("#after_press_dies__table tbody tr.selected-record").removeClass(
    "selected-record"
  );
  $("#racking_dies__table tbody tr.selected-record").removeClass(
    "selected-record"
  );
  $(this).toggleClass("selected-record");
  // activate button
  selectedObj = $("#washing_dies__table tr.selected-record");
  if (selectedObj.length != 0) {
    $("#left-arrow__img").attr("src", "./img/right_arrow-3.png");
    // inactive input value
    $("#washing__div select").val("0").addClass("required-input");
    $("#washing__div select").parent().addClass("inactive__div");
    $("#washing_date__input").parent().addClass("inactive__div");
  } else {
    $("#left-arrow__img").attr("src", "./img/right_arrow-4.png");
    // active input value
    $("#washing__div select").val("0").addClass("required-input");
    $("#washing__div select").parent().removeClass("inactive__div");
    $("#washing_date__input").parent().removeClass("inactive__div");
  }
});

$(document).on("click", "#racking_dies__table tbody tr", function () {
  $(this).toggleClass("selected-record");

  $("#after_press_dies__table tbody tr.selected-record").removeClass(
    "selected-record"
  );
  $("#washing_dies__table tbody tr.selected-record").removeClass(
    "selected-record"
  );
});

$("#right-arrow__img").on("click", function () {
  const now = new Date();
  const hours = now.getHours();
  const minutes = now.getMinutes();
  const seconds = now.getSeconds();
  const currentTime = `${hours}:${minutes}:${seconds}`;
  const currentDayteTime = $("#washing_date__input").val() + " " + currentTime;
  const currentDayte = $("#washing_date__input").val();
  const tankNumber = $("#tank_number__select").val();
  const staffId = $("#staff__select").val();
  const note = $("#note__textarea").val();
  const data = [];
  let status;
  let dieIdObj;

  dieIdObj = $("#after_press_dies__table tr.selected-record td:nth-child(1)");
  switch (washingOrRacking) {
    case "washing":
      status = 4;
      break;
    case "racking":
      status = 10;
      break;
  }

  dieIdObj.each(function () {
    data.push([
      $(this).html(),
      currentDayteTime,
      tankNumber,
      currentDayte,
      staffId,
      status,
      note,
    ]);
  });

  // console.log(data);
  // return;

  const fileName = "./php/DieMaitenance/InsDieStatus.php";
  const sendData = {
    tableData: JSON.stringify(data),
  };
  myAjax.myAjax(fileName, sendData);

  makeAfterPressTalbe();
  switch (washingOrRacking) {
    case "washing":
      makeWashingDieTable();
      break;
    case "racking":
      makeRackingTable();
      break;
  }

  // color the selected dies name
  switch (washingOrRacking) {
    case "washing":
      $("#washing_dies__table tbody tr").each(function () {
        const cellText = $(this).find("td").eq(0).text();
        const targetTr = $(this);
        dieIdObj.each(function () {
          if (cellText === $(this).html()) {
            targetTr.addClass("selected-record");
          }
        });
        // reset input values
      });
      $("#tank_number__select").val(0).addClass("required-input");
      $("#staff__select").val(0).addClass("required-input");
      $(this).prop("disabled", true);
      break;
    case "racking":
      $("#racking_dies__table tbody tr").each(function () {
        const cellText = $(this).find("td").eq(0).text();
        const targetTr = $(this);
        dieIdObj.each(function () {
          if (cellText === $(this).html()) {
            targetTr.addClass("selected-record");
          }
        });
      });
      // reset input values
      $("#rack-staff__select").val("0").addClass("required-input");
      $("#note__textarea").val("");
      break;
  }
  $("#right-arrow__img").attr("src", "./img/right_arrow-4.png");
});

$(document).on("click", "#left-arrow__img", function () {
  const dieStatusIdObj = $(
    "#washing__table tr.selected-record td:nth-child(2)"
  );
});

$(document).on("click, change", "#washing__div", function () {
  checkWashingCondition();
});

$(document).on("click, change", "#racking__div", function () {
  checkRackingCondition();
});

$(document).on("click", "#after_press_dies__table", function () {
  if (washingOrRacking === "washing") {
    checkWashingCondition();
  } else if (washingOrRacking === "racking") {
    checkRackingCondition();
  }
});

function checkWashingCondition() {
  let inputValidation = true;
  // Input Validation
  $("#washing__div .save-data").each(function () {
    if ($(this).val() == 0) {
      inputValidation = false;
    }
  });

  if ($("#after_press_dies__table .selected-record").length == 0) {
    inputValidation = false;
  }

  if (inputValidation) {
    $("#right-arrow__img").attr("src", "./img/right_arrow-3.png");
  } else {
    $("#right-arrow__img").attr("src", "./img/right_arrow-4.png");
  }
}

$("#left-arrow__img").on("click", function () {
  const fileName = "./php/DieMaitenance/DelDieStatus.php";
  let dieStatusIdObj;
  let sendData = new Object();
  let dieStatusId = [];

  switch (washingOrRacking) {
    case "washing":
      dieStatusIdObj = $(
        "#washing_dies__table tr.selected-record td:nth-child(2)"
      );
      break;
    case "racking":
      dieStatusIdObj = $(
        "#racking_dies__table tr.selected-record td:nth-child(2)"
      );
      break;
  }

  dieStatusIdObj.each(function () {
    dieStatusId.push(Number($(this).html()));
  });

  sendData = {
    dieStatudId: dieStatusId,
  };
  myAjax.myAjax(fileName, sendData);

  // return;
  makeAfterPressTalbe();
  makeWashingDieTable();
  makeRackingTable();

  $("#left-arrow__img").attr("src", "./img/right_arrow-4.png");
  $("#washing__div select").val("0").addClass("required-input");
  $("#washing__div select").parent().removeClass("inactive__div");
  $("#washing_date__input").parent().removeClass("inactive__div");
});

function makeRackingStaffSelect() {
  const fileName = "./php/DieMaitenance/SelStaffList.php";
  const sendData = {
    staffOrder: staffOrderMode,
  };
  myAjax.myAjax(fileName, sendData);
  $("#rack-staff__select").empty();
  $("#rack-staff__select").append($("<option>").html("-").val(0));

  ajaxReturnData.forEach(function (value) {
    $("<option>")
      .val(value["id"])
      .html(value["staff_name"])
      .appendTo("#rack-staff__select");
  });
}

function makeRackingTable() {
  const fileName = "./php/DieMaitenance/SelRackingDies.php";
  const sendData = {
    dieName: "%",
  };

  $("#racking_dies__table tbody").empty();
  myAjax.myAjax(fileName, sendData);

  rackingDieTable = ajaxReturnData;
  // summaryTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#racking_dies__table tbody"));
}

function checkRackingCondition() {
  let inputValidation = true;
  // Input Validation
  $("#racking__div .save-data").each(function () {
    if ($(this).val() == 0) {
      inputValidation = false;
    }
  });

  if ($("#after_press_dies__table .selected-record").length == 0) {
    inputValidation = false;
  }

  if (inputValidation) {
    console.log("hello");
    $("#right-arrow__img").attr("src", "./img/right_arrow-3.png");
  } else {
    $("#right-arrow__img").attr("src", "./img/right_arrow-4.png");
  }
}

$(document).on("click change", "#racking__div", function () {
  console.log("hello");
  uncheckRackingCondition();
});

function uncheckRackingCondition() {
  let inputValidation = true;
  // Input Validation

  if ($("#racking_dies__table .selected-record").length == 0) {
    inputValidation = false;
  }
  let temp;
  temp = $("#racking_dies__table .selected-record");
  console.log(temp);

  if (inputValidation) {
    $("#left-arrow__img").attr("src", "./img/right_arrow-3.png");
  } else {
    $("#left-arrow__img").attr("src", "./img/right_arrow-4.png");
  }
}

$(document).on("keyup", "#after-press-die-number-sort__text", function () {
  $(this).val($(this).val().toUpperCase()); // 小文字を大文字に
  const text = $(this).val();

  $("#after_press_dies__table tbody").empty();

  summaryTable.forEach(function (trVal) {
    if (trVal["die_number"].startsWith(text)) {
      var newTr = $("<tr>");
      Object.keys(trVal).forEach(function (tdVal) {
        $("<td>").html(trVal[tdVal]).appendTo(newTr);
      });
      $(newTr).appendTo("#after_press_dies__table tbody");
    }
  });
});

$(document).on("keyup", "#washing-die-number-sort__text", function () {
  $(this).val($(this).val().toUpperCase()); // 小文字を大文字に
  const text = $(this).val();

  $("#washing_dies__table tbody").empty();

  washingDieTable.forEach(function (trVal) {
    if (trVal["die_number"].startsWith(text)) {
      var newTr = $("<tr>");
      Object.keys(trVal).forEach(function (tdVal) {
        $("<td>").html(trVal[tdVal]).appendTo(newTr);
      });
      $(newTr).appendTo("#washing_dies__table tbody");
    }
  });
});

$(document).on("keyup", "#racking-die-number-sort__text", function () {
  $(this).val($(this).val().toUpperCase()); // 小文字を大文字に
  const text = $(this).val();

  $("#racking_dies__table tbody").empty();

  rackingDieTable.forEach(function (trVal) {
    if (trVal["die_number"].startsWith(text)) {
      var newTr = $("<tr>");
      Object.keys(trVal).forEach(function (tdVal) {
        $("<td>").html(trVal[tdVal]).appendTo(newTr);
      });
      $(newTr).appendTo("#racking_dies__table tbody");
    }
  });
});

$(document).on("click", "#radio__button button.inactive__button", function () {
  $(".radio__button button:not(.inactive__button)").addClass(
    "inactive__button"
  );
  $(this).removeClass("inactive__button");
});

$(document).on("click", "#test__button", function () {
  var temp;
  console.log("hello");
  // temp = ajaxFileUpload();
  console.log(uploadFile);
  // uploadFile.push(ajaxFileUpload());
});

function ajaxFileUpload() {
  const formData = new FormData();
  let responseData;
  formData.append("file", $("#fileInput__input")[0].files[0]);

  $.ajax({
    url: "./php/DieMaitenance/upload.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    cache: false,
    async: false,
    success: function (response) {
      console.log("sccess to file upload");
      responseData = response;
    },
    error: function (error) {
      console.log("error to upload:" + error);
    },
  });
  return responseData;
}

$(document).on("change", "#fileInput__input", function () {
  const fileName = $(this).val().split("\\").pop();
  console.log(fileName);
  $("#file_name__label").html(fileName);
});

$(document).on("click", "#fixing-die__table thead", function () {
  makeFixDieList();
});

function makeFixDieList() {
  const fileName = "./php/DieMaitenance/SelFixDieList.php";
  const sendData = {
    machine: "Dummy",
  };

  myAjax.myAjax(fileName, sendData);
  fixDieTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#fixing-die__table tbody"));
}

$(document).on("keyup", "#fix-die-list__text", function () {
  $(this).val($(this).val().toUpperCase()); // 小文字を大文字に
  const text = $(this).val();

  $("#fixing-die__table tbody").empty();

  fixDieTable.forEach(function (trVal) {
    if (trVal["die_number"].startsWith(text)) {
      var newTr = $("<tr>");
      Object.keys(trVal).forEach(function (tdVal) {
        $("<td>").html(trVal[tdVal]).appendTo(newTr);
      });
      $(newTr).appendTo("#fixing-die__table tbody");
    }
  });
});

$(document).on("click", "#fixing-die__table tbody tr", function () {
  if ($(this).hasClass("selected-record")) {
    $(this).removeClass("selected-record");
    in_activeFix();
  } else {
    $("#fixing-die__table tr.selected-record").removeClass("selected-record");
    $(this).addClass("selected-record");
    activeFix();
  }
});

function activeFix() {
  $("#fix-content__div").removeClass("inactive__div");
}

function in_activeFix() {
  $("#fix-content__div").addClass("inactive__div");
}

$(document).on("change", "#uploadForm", function () {
  console.log("hello");

  let fileObject;
  let newImg;
  fileObject = JSON.parse(ajaxFileUpload());
  uploadFile.push(fileObject.fileName);

  newImg = $("<img>").attr(
    "src",
    "./upload/02_die_maitenance/" + fileObject.fileName
  );
  newImg = newImg.attr("alt", fileObject.fileName);
  $("#picture__div").append(newImg);

  $("#fileInput__input").val("");
  $("#file_name__label").html("no file");
});

$(document).on("click", "#picture__div img", function () {
  const fileName = $(this).attr("alt");
  $("#modal-img").attr("src", "./upload/02_die_maitenance/" + fileName);
  $("#modal-img").attr("alt", fileName);

  $("#modal").fadeIn();
});

$(document).on("click", "#close-modal__button", function () {
  $("#modal").fadeOut();
});

$(document).on("click", "#delete-picture__button", function () {
  $("#delete-confirm").fadeIn();
});

$(document).on("click", "#close-confirm__button", function () {
  $("#delete-confirm").fadeOut();
});

$(document).on("click", "#delete-picture-confirm__button", function () {
  let targetImgObj;
  const imgObj = $("#picture__div img");
  targetImgObj = $("#modal-img");

  imgObj.each(function () {
    const fileName = $(this).attr("alt");
    if (targetImgObj.attr("alt") === fileName) {
      $(this).remove();
      $("#delete-confirm").fadeOut();
    }
  });
  $("#modal").fadeOut();
});
