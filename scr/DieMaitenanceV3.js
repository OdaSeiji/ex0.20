let summaryTable = new Object();
let washingDieTable = new Object();
let rackingDieTable = new Object();
let fixDieTable = new Object();
let allDiesTable = new Object();
let nitridingTable = new Object();
let allNitridingTable = new Object();
let ajaxReturnData;
let tableFilterConfig = {
  targetTableBody: null,
  targetTableContent: null,
  targetColumnName: "",
  filterText: "",
};
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

// 2 min after load excute
window.onload = function () {
  setTimeout(function () {
    // makeRackingTable();
    // makeFixDieList();
    // makeFixStaffSelectSelect();
    // makeAllDiesStatusTable();
    // makeRackingStaffSelect();
  }, 1000); // 1000ミリ秒 = 1秒
};

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

  $("#washing_date__input").val(formattedDate);
  $("#racking_date__input").val(formattedDate);
  $("#racking_date-2__input").val(formattedDate);
  $("#fixing-date__input").val(formattedDate);

  makeAfterPressTalbe();
  applyHighlightToAfterPressTable();
  // makeNitridingTable();
  // applyHighlightToNitridingTable();
  // makeAllNitridingRecordTable();

  makeWashingDieTable();

  makeRackingTable();

  // makeFixDieList();

  // makeAllDiesStatusTable();
  // makeNitridingHistoryTable();

  makeWashingStaffSelect();
});

function makeAfterPressTalbe() {
  var fileName = "./php/DieMaitenance/SelAfterPressDie.php";
  var sendData = {
    machine: "Dummy",
  };

  myAjax.myAjax(fileName, sendData);
  summaryTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#after_press_dies__table tbody"));
}

function makeNitridingTable() {
  var fileName = "./php/DieMaitenance/SelNitriding.php";
  var sendData = {
    machine: "Dummy",
  };
  myAjax.myAjax(fileName, sendData);
  nitridingTable = ajaxReturnData;
  // console.log(ajaxReturnData);
  fillTableBody(ajaxReturnData, $("#nitriding__table tbody"));
}

function makeNitridingHistoryTable(dieId) {
  var fileName = "./php/DieMaitenance/SelNitridingHistory.php";
  var sendData = {
    dieId: dieId,
  };
  myAjax.myAjax(fileName, sendData);
  // console.log(ajaxReturnData);
  fillTableBody(ajaxReturnData, $("#nitriding-hisotry__table tbody"));
}

function makeAllNitridingRecordTable() {
  var fileName = "./php/DieMaitenance/SelAllNitridingRecord.php";
  var sendData = {
    dieId: "dummy",
  };
  myAjax.myAjax(fileName, sendData);
  allNitridingTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#all-nitriding-record__table tbody"));
}

function makeWashingDieTable() {
  var fileName = "./php/DieMaitenance/SelWashingDie.php";
  var sendData = {
    machine: "Dummy",
  };
  myAjax.myAjax(fileName, sendData);
  fillTableBody(ajaxReturnData, $("#washing-dies__table tbody"));
  fillTableBody(ajaxReturnData, $("#washing-dies-2__table tbody"));
  washingDieTable = ajaxReturnData;
}

function makeRackingTable() {
  const fileName = "./php/DieMaitenance/SelRackingDies.php";
  const sendData = {
    dieName: "%",
  };

  $("#racking-dies__table tbody").empty();
  $("#racking_dies-2__table tbody").empty();
  myAjax.myAjax(fileName, sendData);

  rackingDieTable = ajaxReturnData;
  // summaryTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#racking-dies__table tbody"));
  fillTableBody(ajaxReturnData, $("#racking_dies-2__table tbody"));
}

function makeFixDieList() {
  const fileName = "./php/DieMaitenance/SelFixDieList.php";
  const sendData = {
    machine: "Dummy",
  };

  myAjax.myAjax(fileName, sendData);
  fixDieTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#fixing-die__table tbody"));
}

function makeAllDiesStatusTable() {
  var fileName = "./php/DieMaitenance/SelAllDiesStatus.php";
  var sendData = {
    machine: "Dummy",
  };

  console.log("hello");

  myAjax.myAjax(fileName, sendData);
  allDiesTable = ajaxReturnData;
  fillTableBody(ajaxReturnData, $("#all-dies-status__table tbody"));
}

function applyHighlightToAfterPressTable() {
  const targetObj = $("#after_press_dies__table tbody tr");
  targetObj.each(function () {
    const $row = $(this);
    const result = $row.find("td:nth-child(7)").text();
    if (result == "Wash") {
      $row.addClass("redHighlight");
    }
  });
}

function applyHighlightToNitridingTable() {
  const lnegthThreshold260 = 3.5;
  const lnegthThreshold300 = 2.5;
  const washingThreshold = 5;
  const targetObj = $("#nitriding__table tbody tr");
  targetObj.each(function () {
    const $row = $(this);
    const profileLength = parseFloat($row.find("td:nth-child(3)").text());
    const washingCount = parseInt($row.find("td:nth-child(4)").text());
    const dieDiamater = parseInt($row.find("td:nth-child(7)").text());

    if (dieDiamater >= 300) {
      if (
        (!isNaN(profileLength) && profileLength > lnegthThreshold300) ||
        (!isNaN(washingCount) && washingCount >= washingThreshold)
      ) {
        $row.addClass("redHighlight");
      }
    } else if (dieDiamater <= 260) {
      if (
        (!isNaN(profileLength) && profileLength > lnegthThreshold260) ||
        (!isNaN(washingCount) && washingCount >= washingThreshold)
      ) {
        $row.addClass("redHighlight");
      }
    }
  });
}

function makeWashingStaffSelect() {
  const fileName = "./php/DieMaitenance/SelStaffList.php";
  const sendData = {
    staffOrder: staffOrderMode,
  };
  myAjax.myAjax(fileName, sendData);
  $("#wash-staff__select").empty().append($("<option>").html("-").val(0));
  ajaxReturnData.forEach(function (value) {
    $("<option>")
      .val(value["id"])
      .html(value["staff_name"])
      .appendTo("#wash-staff__select");
  });
}
// color record
$(document).on("click", "table tbody tr", function () {
  $(this).toggleClass("selected-record");

  const targetRow = $(this)
    .parent()
    .parent()
    .parent()
    .siblings()
    .find("tr.selected-record");
  targetRow.removeClass("selected-record");
});

$(document).on("click", "#nitriding__table tbody tr", function () {
  const dieId = $(this).find("td").eq(0).html();
  const dieNumber = $(this).find("td").eq(1).html();
  $("#nitriding-history__caption").html("'" + dieNumber + "' history");
  makeNitridingHistoryTable(dieId);
});

$(document).on(
  "keydown",
  "#nitriding-fileter__input, " +
    "#after-press-die-number-sort__text, " +
    "#washing-die-number-sort__text",
  function (event) {
    if (event.key === "Enter") {
      $(this).blur();
    }
  }
);

$(document).on(
  "keyup",
  "#nitriding-fileter__input, #all-nitriding__input, " +
    "#after-press-die-number-sort__text," +
    "#washing-die-number-sort__text, " +
    "#racking-die-number-sort__text",
  function () {
    $(this).val($(this).val().toUpperCase()); // 小文字を大文字に
  }
);

$(document).on("change", "#nitriding-fileter__input", function () {
  tableFilterConfig = {
    targetTableBody: $("#nitriding__table tbody"),
    targetTableContent: nitridingTable,
    targetColumnName: "die_number",
    filterText: $(this).val(),
  };
  tableFilter(tableFilterConfig);
  applyHighlightToNitridingTable();
});

$(document).on("change", "#all-nitriding__input", function () {
  tableFilterConfig = {
    targetTableBody: $("#all-nitriding-record__table tbody"),
    targetTableContent: allNitridingTable,
    targetColumnName: "die_number",
    filterText: $(this).val(),
  };
  tableFilter(tableFilterConfig);
});

$(document).on("change", "#after-press-die-number-sort__text", function () {
  tableFilterConfig = {
    targetTableBody: $("#after_press_dies__table tbody"),
    targetTableContent: summaryTable,
    targetColumnName: "die_number",
    filterText: $(this).val(),
  };
  tableFilter(tableFilterConfig);
});

$(document).on("change", "#washing-die-number-sort__text", function () {
  tableFilterConfig = {
    targetTableBody: $("#washing-dies__table tbody"),
    targetTableContent: washingDieTable,
    targetColumnName: "die_number",
    filterText: $(this).val(),
  };
  tableFilter(tableFilterConfig);
});

$(document).on("change", "#racking-die-number-sort__text", function () {
  tableFilterConfig = {
    targetTableBody: $("#racking-dies__table tbody"),
    targetTableContent: rackingDieTable,
    targetColumnName: "die_number",
    filterText: $(this).val(),
  };
  tableFilter(tableFilterConfig);
});

function tableFilter(tableFilterConfig) {
  tableFilterConfig.targetTableBody.empty();

  tableFilterConfig.targetTableContent.forEach(function (rowData) {
    // if (
    //   rowData[tableFilterConfig.targetColumnName].startsWith(
    //     tableFilterConfig.filterText
    //   )
    if (
      new RegExp(`^${tableFilterConfig.filterText}`).test(
        rowData[tableFilterConfig.targetColumnName]
      )
    ) {
      let filteredRow = $("<tr>");
      Object.keys(rowData).forEach(function (tdVal) {
        $("<td>").html(rowData[tdVal]).appendTo(filteredRow);
      });
      $(filteredRow).appendTo(tableFilterConfig.targetTableBody);
    }
  });
}

// after press table sort
$(document).on(
  "click",
  "#after_press_dies__table th:nth-child(2), " +
    "#after_press_dies__table th:nth-child(7)",
  function () {
    let isDescending = false;
    let className;
    const sortColumnNameTable = {
      2: "yymmdd",
      7: "action",
    };
    let sortColumnName = sortColumnNameTable[Number($(this).index() + 1)];

    $(this).parent().find("th.sortActive").removeClass("sortActive");
    $(this).addClass("sortActive").toggleClass("isDescending");
    className = $(this).attr("class");

    if (className.includes("isDescending")) {
      isDescending = true;
      summaryTable.sort((a, b) => {
        const primary = a[sortColumnName].localeCompare(b[sortColumnName]);
        if (primary !== 0) return primary;

        // sortColumnName が等しい場合は die_number で比較（昇順）
        return a.die_number - b.die_number;
      });
    } else {
      isDescending = false;
      summaryTable.sort((a, b) => {
        const primary = b[sortColumnName].localeCompare(a[sortColumnName]);
        if (primary !== 0) return primary;
        return a.die_number - b.die_number; // 昇順
      });
    }
    fillTableBody(summaryTable, $("#after_press_dies__table tbody"));
    applyHighlightToAfterPressTable();
  }
);

// nitriding table sort
$(document).on(
  "click",
  "#nitriding__table th:nth-child(3), #nitriding__table th:nth-child(4), #nitriding__table th:nth-child(5), #nitriding__table th:nth-child(6)",
  function () {
    let isDescending = false;
    let className;
    const sortColumnNameTalbe = {
      3: "profile_length_after_nitriding",
      4: "washing_count_after_nitriding",
      5: "total_profile_length",
      6: "total_washing_count",
    };
    const sortColumnName = sortColumnNameTalbe[Number($(this).index()) + 1];

    // console.log(nitridingTable);
    $("#nitriding__table th.sortActive").removeClass("sortActive");
    $(this).addClass("sortActive").toggleClass("isDescending");
    className = $(this).attr("class");

    if (className.includes("isDescending")) {
      isDescending = true;
    } else {
      isDescending = false;
    }

    let tableSortConfig = {
      tableContent: nitridingTable,
      sortColumnName: sortColumnName,
      isDescending: isDescending,
    };
    nitridingTable = tableDataSort(tableSortConfig);

    fillTableBody(nitridingTable, $("#nitriding__table tbody"));
    applyHighlightToNitridingTable();
  }
);

function tableDataSort(tableSortConfig) {
  tableSortConfig.tableContent.sort((a, b) => {
    const aCount = parseFloat(a[tableSortConfig.sortColumnName]);
    const bCount = parseFloat(b[tableSortConfig.sortColumnName]);
    if (tableSortConfig.isDescending) {
      // 降順：大きい順に並べる
      return bCount - aCount;
    } else {
      // 昇順：小さい順に並べる
      return aCount - bCount;
    }
  });

  return tableSortConfig.tableContent;
}

// ===============================================================
// ===============================================================
// ===============================================================
// ===============================================================

// select washing-dies
$(document).on("click", ".washing-dies__wrapper", function () {
  $(this).removeClass("inactive__div");
  $(".racking-dies__wrapper").addClass("inactive__div");

  $("#tank_number__select").attr("disabled", false);
  $("#wash-staff__select").attr("disabled", false);
  $("#washing_date__input").attr("disabled", false);

  $("#racking-dies__table .selected-record").removeClass("selected-record");

  $("#racking-die-number-sort__text").val("");

  washingOrRacking = "washing";
});

// select racking-dies
$(document).on("click", ".racking-dies__wrapper.inactive__div", function () {
  $(this).removeClass("inactive__div");
  $(".washing-dies__wrapper").addClass("inactive__div");

  $("#tank_number__select")
    .val("0")
    .attr("disabled", true)
    .addClass("required-input");
  $("#wash-staff__select")
    .val("0")
    .attr("disabled", true)
    .addClass("required-input");
  $("#washing_date__input").attr("disabled", true);

  $("#washing-dies__table .selected-record").removeClass("selected-record");

  $("#washing-die-number-sort__text").val("");

  washingOrRacking = "racking";
});

$(document).on("change", "select", function () {
  $(this).toggleClass("required-input", $(this).val() == "0");
});

// 1st row actvation arrow
$(document).on(
  "click change",
  ".after-press-dies__wrapper, .washing-dies__wrapper",
  function () {
    const afterPressSelectRows = $(
      "#after_press_dies__table tr.selected-record"
    );
    const washingSelectRows = $("#washing-dies__table tr.selected-record");
    const selectElements = $("div.washing-dies__wrapper").find("select");
    let rightArrowFlag = true;
    let leftArrowFlag = true;

    if (afterPressSelectRows.length == 0) {
      rightArrowFlag = false;
    }
    if (washingSelectRows.length == 0) {
      leftArrowFlag = false;
    }

    selectElements.each(function () {
      if ($(this).val() == 0) {
        rightArrowFlag = false;
      }
    });

    if (rightArrowFlag) {
      $("#right-arrow__img")
        .attr("src", "./img/right_arrow-active.png")
        .addClass("active");
    } else {
      $("#right-arrow__img")
        .attr("src", "./img/right_arrow-inactive.png")
        .removeClass("active");
    }
    // console.log(leftArrowFlag);
    if (leftArrowFlag) {
      $("#left-arrow__img")
        .attr("src", "./img/right_arrow-active.png")
        .addClass("active");
    } else {
      $("#left-arrow__img")
        .attr("src", "./img/right_arrow-inactive.png")
        .removeClass("active");
    }
  }
);

$(document).on("click", "#right-arrow__img.active", function () {
  const now = new Date();
  const hours = now.getHours();
  const minutes = now.getMinutes();
  const seconds = now.getSeconds();
  const currentTime = `${hours}:${minutes}:${seconds}`;
  const currentDayteTime = $("#washing_date__input").val() + " " + currentTime;
  const currentDate = $("#washing_date__input").val();
  const tankNumber = $("#tank_number__select").val();
  const note = $("#note__textarea").val();
  const data = [];
  const dieIdObj = $(
    "#after_press_dies__table tr.selected-record td:nth-child(1)"
  );
  let staffId;
  let status;

  switch (washingOrRacking) {
    case "washing":
      status = 4;
      staffId = Number($("#wash-staff__select").val());
      break;
    case "racking":
      status = 10;
      staffId = Number($("#rack-staff__select").val());
      break;
  }

  dieIdObj.each(function () {
    data.push([
      $(this).html(), // die_id
      currentDayteTime, // input_date_time
      tankNumber, // tank number
      currentDate, // input_date
      staffId,
      status, // dies status = 4
      note,
    ]);
  });

  console.log(data);
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
      $("#washing-dies__table tbody tr").each(function () {
        const dieIdText = $(this).find("td").eq(0).text();
        const targetTr = $(this);
        dieIdObj.each(function () {
          if (Number(dieIdText) === Number($(this).html())) {
            targetTr.addClass("selected-record");
          }
        });
      });
      // reset input values
      $("#tank_number__select").val(0).addClass("required-input");
      $("#wash-staff__select").val(0).addClass("required-input");
      // $(this).prop("disabled", true);
      $("#right-arrow__img")
        .attr("src", "./img/right_arrow-inactive.png")
        .removeClass("active");
      break;
    case "racking":
      $("#racking_dies__table tbody tr").each(function () {
        const cellText = $(this).find("td").eq(0).text();
        const targetTr = $(this);
        dieIdObj.each(function () {
          if (Number(dieIdText) === Number($(this).html())) {
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

$("#left-arrow__img").on("click", function () {
  const fileName = "./php/DieMaitenance/DelDieStatus.php";
  let dieStatusIdObj;
  let sendData = new Object();
  let dieStatusId = [];
  let dieId = [];

  switch (washingOrRacking) {
    case "washing":
      dieStatusIdObj = $("#washing-dies__table tr.selected-record");
      break;
    case "racking":
      dieStatusIdObj = $(
        "#racking-dies__table tr.selected-record td:nth-child(2)"
      );
      break;
  }
  dieStatusIdObj.each(function () {
    dieStatusId.push(Number($(this).find("td").eq(1).html()));
    dieId.push(Number($(this).find("td").eq(0).html()));
  });

  sendData = {
    dieStatudId: dieStatusId,
  };

  myAjax.myAjax(fileName, sendData);

  // return;
  makeAfterPressTalbe();
  makeWashingDieTable();
  makeRackingTable();

  $("#after_press_dies__table tbody tr").each(function () {
    const dieIdText = $(this).find("td").eq(0).text();
    const targetTr = $(this);
    // dieIdObj.each(function () {
    //   if (Number(dieIdText) === Number($(this).html())) {
    //     targetTr.addClass("selected-record");
    //   }
    // });
    dieId.forEach(function (value, index) {
      if (Number(dieIdText) === Number(value)) {
        targetTr.addClass("selected-record");
      }
    });
  });

  $("#left-arrow__img")
    .attr("src", "./img/right_arrow-inactive.png")
    .removeClass("active");
});
