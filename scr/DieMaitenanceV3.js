let summaryTable = new Object();
let washingDieTable = new Object();
let rackingDieTable = new Object();
let fixDieTable = new Object();
let allDiesTable = new Object();
let nitridingTable = new Object();
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
  console.log("Hello");
  makeNitridingTable();
  applyHighlightToNitridingTable();
  // makeNitridingHistoryTable();
});

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

function applyHighlightToNitridingTable() {
  const lnegthThreshold = 3;
  const washingThreshold = 5;
  const targetObj = $("#nitriding__table tbody tr");
  targetObj.each(function () {
    const $row = $(this);
    const profileLength = parseInt($row.find("td:nth-child(3)").text());
    const washingCount = parseInt($row.find("td:nth-child(4)").text());
    if (
      (!isNaN(profileLength) && profileLength > lnegthThreshold) ||
      (!isNaN(washingCount) && washingCount >= washingThreshold)
    ) {
      $row.addClass("redHighlight");
    }
  });
}
// color record
$(document).on("click", "table tbody tr", function () {
  $(this).toggleClass("selected-record");
});

$(document).on("click", "#nitriding__table tbody tr", function () {
  const dieId = $(this).find("td").eq(0).html();
  const dieNumber = $(this).find("td").eq(1).html();
  $("#nitriding-history__caption").html("'" + dieNumber + "' history");
  makeNitridingHistoryTable(dieId);
});

$(document).on("keydown", "#nitriding-fileter__input", function (event) {
  if (event.key === "Enter") {
    $(this).blur();
  }
});

$(document).on("keyup", "#nitriding-fileter__input", function () {
  $(this).val($(this).val().toUpperCase()); // 小文字を大文字に
});

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

function tableFilter(tableFilterConfig) {
  tableFilterConfig.targetTableBody.empty();

  tableFilterConfig.targetTableContent.forEach(function (rowData) {
    if (
      rowData[tableFilterConfig.targetColumnName].startsWith(
        tableFilterConfig.filterText
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
