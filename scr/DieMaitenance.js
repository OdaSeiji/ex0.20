var summaryTable = new Object();
var washingDieTable = new Object();
var ajaxReturnData;
var staffOrderMode = 4;
let timeout;

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
  makeStaffSelectSelect();
  $("#washing_date__input").val(formattedDate);
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
  fillTableBody(ajaxReturnData, $("#washing_dies__table_cp tbody"));
  washingDieTable = ajaxReturnData;
  makeStaffSelectSelect();
}

function fillDieStatusHistoryTable(dies_id) {
  // dies_id = 100;
  var fileName = "./php/DieMaitenance/SelDieStatus.php";
  var sendData = {
    die_id: dies_id,
  };
  myAjax.myAjax(fileName, sendData);
  fillTableBody(ajaxReturnData, $("#status-history__table tbody"));
}

function fillDieHistoryTable(dies_id) {
  var fileName = "./php/DieMaitenance/SelDiePressHistory.php";
  var sendData = {
    dies_id: dies_id,
  };
  myAjax.myAjax(fileName, sendData);
  fillTableBody(ajaxReturnData, $("#die-history__table tbody"));
}

$(document).on("click", "table#after_press_dies__table tbody tr", function () {
  var targetObj;
  $(this).toggleClass("selected-record");
  checkWashingCondition();

  // make target die history table
  targetObj = $(this).find("td:first");
  console.log(targetObj.text());
  fillDieStatusHistoryTable(targetObj.text());
  $("#die_name").html($(this).find("td").eq(2).text());
});

$(document).on("click", "table#washing_dies__table tbody tr", function () {
  $(this).toggleClass("selected-record");
  // reset input values
  $("#tank_number__select").val(0).addClass("required-input");
  $("#staff__select").val(0).addClass("required-input");
});

$(document).on("click", "table#racking_dies__table tbody tr", function () {
  $(this).toggleClass("selected-record");
  // reset input values
  $("#tank_number__select").val(0).addClass("required-input");
  $("#staff__select").val(0).addClass("required-input");
});

$(document).on("focus", "#edit-lotnumber__input", function () {
  const fileName = "./php/DieMaitenance/SelNoMantenanceDie.php";
  const sendData = {
    machine: "Dummy",
  };
  myAjax.myAjax(fileName, sendData);
});

$(document).on("change", "select", function () {
  if ($(this).val() === "0") {
    $(this).addClass("required-input");
  } else {
    $(this).removeClass("required-input");
  }
});

$(".after_press_dies select").on("change", function () {
  checkWashingCondition();
});

$("#washing_date__input").on("change", function () {
  checkWashingCondition();
});

function checkWashingCondition() {
  const selectDieObj = $(
    "#after_press_dies__table tr.selected-record td:nth-child(1)"
  );
  const washingDate = $("#washing_date__input").val();
  const washingTank = $("#tank_number__select").val();
  const staffId = $("#staff__select").val();

  console.log("Hello");

  if (selectDieObj.length != 0 && washingTank != 0 && staffId != 0) {
    $("#washing__button").prop("disabled", false);
  }
}

$("#washing__button").on("click", function () {
  const now = new Date();
  const hours = now.getHours();
  const minutes = now.getMinutes();
  const seconds = now.getSeconds();
  const currentTime = `${hours}:${minutes}:${seconds}`;
  const currentDayteTime = $("#washing_date__input").val() + " " + currentTime;
  const currentDayte = $("#washing_date__input").val();
  const tankNumber = $("#tank_number__select").val();
  const staffId = $("#staff__select").val();
  const data = [];
  var dieIdObj;

  dieIdObj = $("#after_press_dies__table tr.selected-record td:nth-child(1)");

  dieIdObj.each(function () {
    data.push([
      $(this).html(),
      currentDayteTime,
      tankNumber,
      currentDayte,
      staffId,
    ]);
  });

  const fileName = "./php/DieMaitenance/InsWashingDie.php";
  const sendData = {
    tableData: JSON.stringify(data),
  };
  myAjax.myAjax(fileName, sendData);

  makeAfterPressTalbe();
  makeWashingDieTable();

  // color the selected dies name
  $("#washing_dies__table tbody tr").each(function () {
    const cellText = $(this).find("td").eq(0).text();
    const targetTr = $(this);
    dieIdObj.each(function () {
      if (cellText === $(this).html()) {
        targetTr.css("background-color", "#fffaad");
      }
    });
    // reset input values
    $("#tank_number__select").val(0).addClass("required-input");
    $("#staff__select").val(0).addClass("required-input");
  });

  $("#washing__button").prop("disabled", true);
});

$(document).on("keyup", "#die-number-sort__text", function () {
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

$(document).on("keyup", "#die-number-sort2__text", function () {
  $(this).val($(this).val().toUpperCase()); // 小文字を大文字に
  const text = $(this).val();

  $("#washing_dies__table tbody").empty();
  // $("#washing_dies__table_cp tbody").empty();

  washingDieTable.forEach(function (trVal) {
    if (trVal["die_number"].startsWith(text)) {
      var newTr = $("<tr>");
      Object.keys(trVal).forEach(function (tdVal) {
        $("<td>").html(trVal[tdVal]).appendTo(newTr);
      });
      $(newTr).appendTo("#washing_dies__table tbody");
      // $(newTr).appendTo("#washing_dies__table_cp tbody");
    }
  });
});

function makeStaffSelectSelect() {
  const fileName = "./php/DieMaitenance/SelStaffList.php";
  const sendData = {
    staffOrder: staffOrderMode,
  };
  myAjax.myAjax(fileName, sendData);
  $("#staff__select").empty();
  $("#staff__select").append($("<option>").html("-").val(0));

  ajaxReturnData.forEach(function (value) {
    $("<option>")
      .val(value["id"])
      .html(value["staff_name"])
      .appendTo("#staff__select");
  });
}

$(document).on("click", ".mode-change-button__wrapper button", function () {
  if ($(this).hasClass("active")) {
    // 同じボタンが押された場合は処理を中止
    return;
  }

  $("button.active").removeClass("active");
  $(this).addClass("active");

  switch ($(".mode-change-button__wrapper button.active").attr("id")) {
    case "rack-mode__button":
      makeRackingTable();
      staffOrderMode = 10;
      makeStaffSelectSelect();
      $("#tank_number__select").prop("disabled", true);
      break;
    case "wash-mode__button":
      makeWashingTable();
      staffOrderMode = 4;
      makeStaffSelectSelect();
      $("#tank_number__select").prop("disabled", false);
      break;
  }
});

$(document).on("click", "#test__button", function () {
  const fileName = "./php/DieMaitenance/DelDieStatus.php";
  let afterPressTable = new Object();
  // let afterPressTable = $("#after_press_dies__table tbody tr");

  let sendData = {};
  let removeDieStatusId = [];
  let removeDieId = [];

  $("#washing_dies__table tbody tr.selected-record").each(function () {
    removeDieStatusId.push(Number($(this).find("td").eq(1).text()));
    removeDieId.push(Number($(this).find("td").eq(0).text()));
  });
  // console.log(removeDieStatusId);
  sendData = {
    data: removeDieStatusId,
  };
  // delete selected information from t_dies_status
  myAjax.myAjax(fileName, sendData);
  makeWashingDieTable();
  makeAfterPressTalbe();
  // color selected dies at after press table
  console.log(removeDieId);
  afterPressTable = $("#after_press_dies__table tbody tr");
  afterPressTable.each(function (index, element) {
    const targetRecord = $(this);
    let dieNumber = $(this).find("td").eq(0).html();
    // console.log(dieNumber);

    removeDieId.forEach((deletedId) => {
      // console.log(Number(dieNumber), " : ", deletedId);
      if (Number(dieNumber) === deletedId) {
        console.log("OK");
        targetRecord.addClass("selected-record");
      }
    });
  });
});

function makeWashingTable() {
  const tdTitles = ["dies_id", "id", "Die Number", "Tank", "Input Date"];
  const newRow = $("<tr>");
  const fileName = "./php/DieMaitenance/SelWashingDie.php";

  const sendData = {
    dieName: "%",
  };

  $("#racking_dies__table").find("tr").remove();
  tdTitles.forEach((title) => {
    const td = $("<th>").text(title);
    newRow.append(td);
  });

  $("#racking_dies__table").attr("id", "washing_dies__table");
  newRow.appendTo("#washing_dies__table thead");

  myAjax.myAjax(fileName, sendData);

  washingDieTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#washing_dies__table tbody"));
  fillTableBody(ajaxReturnData, $("#washing_dies__table_cp tbody"));
}

function makeRackingTable() {
  const tdTitles = ["dies_id", "id", "Die Number", "Input Date"];
  const newRow = $("<tr>");
  const fileName = "./php/DieMaitenance/SelRackingDies.php";
  const sendData = {
    dieName: "%",
  };

  $("#washing_dies__table").find("tr").remove();
  tdTitles.forEach((title) => {
    const td = $("<th>").text(title);
    newRow.append(td);
  });

  $("#washing_dies__table").attr("id", "racking_dies__table");
  newRow.appendTo("#racking_dies__table thead");

  myAjax.myAjax(fileName, sendData);

  // summaryTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#racking_dies__table tbody"));
}
