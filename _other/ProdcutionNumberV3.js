// 23/03/26 Fisrt day

let summaryTableEditMode = false;
let ajaxReturnData;
// let inputProductionNumber = "C2Q63A-AD141A20K";
let table = $("#summary__table");
// ソート対象の列を選択
let column = 1; // 例として、2列目を選択する場合
let sortReverse = false;
let sameColumn = false;
let titleNames = new Object();
let summaryTable = new Object();

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
  // make summary table
  readSummaryTable();
  summaryTable = ajaxReturnData;
  makeSummaryTable(summaryTable);
  $("#summary__table_record").html(
    $("#summary__table tbody tr").length + " items"
  );
  // make category1 table
  readCategory1Table();
  // $("#test__button").remove();
});

function readSummaryTable() {
  let fileName;
  let sendData = new Object();
  // read ng list and fill option
  fileName = "./php/ProductionNumber/SelSummaryV3.php";
  sendData = {
    dummy: "dummy",
  };
  myAjax.myAjax(fileName, sendData);
  // summaryTable = ajaxReturnData;
}

function makeSummaryTable(tableObj) {
  $("#summary__table tbody").empty();
  tableObj.forEach(function (trVal) {
    var newTr = $("<tr>");
    Object.keys(trVal).forEach(function (tdVal) {
      $("<td>").html(trVal[tdVal]).appendTo(newTr);
    });
    $(newTr).appendTo("#summary__table tbody");
  });
}

function readCategory1Table() {
  let fileName;
  let sendData = new Object();
  // read ng list and fill option
  fileName = "./php/ProductionNumber/SelCategory1V2.php";
  sendData = {
    dummy: "dummy",
  };
  myAjax.myAjax(fileName, sendData);
  $("#category1__table tbody").empty();
  ajaxReturnData.forEach(function (trVal) {
    var newTr = $("<tr>");
    Object.keys(trVal).forEach(function (tdVal) {
      $("<td>").html(trVal[tdVal]).appendTo(newTr);
    });
    $(newTr).appendTo("#category1__table tbody");
  });
}

$(document).on("mouseover", "#window_close__mark", function () {
  // console.log("hello");
  $("#window_close__mark").attr("src", "./img/close-2.png");
});

$(document).on("mouseout", "#window_close__mark", function () {
  // console.log("hello2");
  $("#window_close__mark").attr("src", "./img/close.png");
});

$(document).on("click", "#window_close__mark", function () {
  // open("about:blank", "_self").close(); // close window
  window.close();
});

$(document).on("keyup", "#production_number", function () {
  $(this).val($(this).val().toUpperCase()); // 小文字を大文字に
  if ($(this).val().length > 3) {
    $(this).removeClass("input-required");
    $("#mode_display").html("Add New Mode");
  } else {
    $(this).addClass("input-required");
  }
});

$(document).on("change", ".main__wrapper select", function () {
  if ($(this).val() != 0) {
    $(this).removeClass("input-required");
  } else {
    $(this).addClass("input-required");
  }
});

$(document).on("keyup", "#production_length", function () {
  if (isNumber($(this).val()) && 1 <= $(this).val() && $(this).val() <= 9) {
    $(this).removeClass("input-required");
  } else {
    $(this).addClass("input-required");
  }
});

$(document).on("keyup", "#circumscribed_circle", function () {
  if (isNumber($(this).val()) && 5 <= $(this).val() && $(this).val() <= 400) {
    $(this).removeClass("input-required");
  } else {
    $(this).addClass("input-required");
  }
});

$(document).on("keyup", "#hardness", function () {
  if (isNumber($(this).val()) && 50 <= $(this).val() && $(this).val() <= 90) {
    $(this).removeClass("input-required");
  } else {
    $(this).addClass("input-required");
  }
});

$(document).on("keyup", "#specific_weight", function () {
  if (isNumber($(this).val()) && 0.1 <= $(this).val() && $(this).val() <= 40) {
    $(this).removeClass("input-required");
  } else {
    $(this).addClass("input-required");
  }
});

$(document).on("keyup", "#cross_section_area", function () {
  if (isNumber($(this).val()) && 2 <= $(this).val() && $(this).val() <= 15000) {
    $(this).removeClass("input-required");
  } else {
    $(this).addClass("input-required");
  }
});

$(document).on("keyup", "#packing_quantity", function () {
  if (isNumber($(this).val()) && 2 <= $(this).val() && $(this).val() <= 1000) {
    $(this).removeClass("input-required");
  } else {
    $(this).addClass("input-required");
  }
});

$(document).on("keyup", "#packing_row", function () {
  if (isNumber($(this).val()) && 2 <= $(this).val() && $(this).val() <= 500) {
    $(this).removeClass("input-required");
  } else {
    $(this).addClass("input-required");
  }
});

$(document).on("keyup", "#packing_column", function () {
  if (isNumber($(this).val()) && 2 <= $(this).val() && $(this).val() <= 500) {
    $(this).removeClass("input-required");
  } else {
    $(this).addClass("input-required");
  }
});

$(document).on("keyup", ".top__wrapper", function () {
  // console.log(checkInput());
  if ($("#mode_display").html() == "Update Mode") {
    if (checkInput()) {
      $("#update__button").prop("disabled", false);
    } else {
      $("#update__button").prop("disabled", true);
    }
  } else {
    if (checkInput()) {
      $("#save__button").prop("disabled", false);
    } else {
      $("#save__button").prop("disabled", true);
    }
  }
});

$(document).on("change", ".top__wrapper", function () {
  if ($("#mode_display").html() == "Update Mode") {
    if (checkInput()) {
      $("#update__button").prop("disabled", false);
    } else {
      $("#update__button").prop("disabled", true);
    }
  } else {
    if (checkInput()) {
      $("#save__button").prop("disabled", false);
    } else {
      $("#save__button").prop("disabled", true);
    }
  }
});

$(document).on("click", ".top__wrapper", function () {
  // console.log(checkInput());
  if ($("#mode_display").html() == "Update Mode") {
    if (checkInput()) {
      $("#update__button").prop("disabled", false);
    } else {
      $("#update__button").prop("disabled", true);
    }
  } else {
    if (checkInput()) {
      $("#save__button").prop("disabled", false);
    } else {
      $("#save__button").prop("disabled", true);
    }
  }
});

function checkInput() {
  let flag = true;
  $(".save-data").each(function (index, element) {
    if ($(element).hasClass("input-required")) {
      flag = false;
    }
  });
  if ($(".top__wrapper table tbody tr.selected-record").length != 2) {
    flag = false;
  }
  return flag;
}

function isNumber(val) {
  let flag = true;

  if (isNaN(val)) {
    flag = false;
  }
  if (val == "") {
    flag = false;
  }
  return flag;
}

$(document).on("click", "#category1__table tbody tr", function () {
  let fileName;
  let sendData = new Object();
  $("#category1__table tbody tr.selected-record").removeClass(
    "selected-record"
  );
  $("#category1__tr").removeAttr("id");
  $(this).addClass("selected-record").attr("id", "category1__tr");

  fileName = "./php/ProductionNumber/SelCategory2V2.php";
  sendData = {
    targetId: $("#category1__tr").find("td").eq(0).html(),
  };
  // console.log(sendData);
  myAjax.myAjax(fileName, sendData);

  $("#category2__table tbody").empty();
  if (ajaxReturnData.length == 0) {
    $("#category2__table tbody").append($("<tr>").append($("<td><td><td>")));
  } else {
    ajaxReturnData.forEach(function (trVal) {
      var newTr = $("<tr>");
      Object.keys(trVal).forEach(function (tdVal) {
        $("<td>").html(trVal[tdVal]).appendTo(newTr);
      });
      $(newTr).appendTo("#category2__table tbody");
    });
  }
});

$(document).on("click", "#category2__table tbody tr", function () {
  $("#category2__table tbody tr.selected-record").removeClass(
    "selected-record"
  );
  $("#category2__tr").removeAttr("id");
  $(this).addClass("selected-record").attr("id", "category2__tr");
});

$(document).on("click", "#save__button", function () {
  let fileName;
  let sendData = new Object();
  let element;
  let tableProdcutionNumber;

  sendData = getInputData();
  inputProductionNumber = sendData.production_number;
  fileName = "./php/ProductionNumber/InsInputData3.php";
  myAjax.myAjax(fileName, sendData);

  $("#summary__table tbody").empty();
  readSummaryTable();

  // Corsol move to new production name row
  $(".selected").removeClass("selected");
  $("#selected-summary__tr").removeAttr("selected-summary__tr");
  $("#summary__table tbody tr").each(function (index, element) {
    tableProdcutionNumber = $(element).find("td").eq(3).html();
    if ($(element).find("td").eq(3).html() == inputProductionNumber) {
      $(element).addClass("selected-record").attr("id", "selected-summary__tr");
    }
  });

  element = document.getElementById("selected-summary__tr");
  element.scrollIntoView({
    behavior: "smooth",
  });

  // Clear input value
  $("input.need-clear").val("").addClass("input-required");
  $("select.need-clear").val("0").addClass("input-required");

  $("#save__button").prop("disabled", true);
});

$(document).on("click", "#update__button", function () {
  let fileName;
  let sendData = new Object();
  // const targetTr = $("#summary_tr").get(0);
  const targetId = $("#summary__tr").find("td").eq(0).text();
  // console.log("hello");
  // console.log(targetId);
  // console.log(getInputData());

  // Update
  sendData = getInputData();
  fileName = "./php/ProductionNumber/UpdateSummaryV3.php";
  myAjax.myAjax(fileName, sendData);

  // Reload Summary Table
  readSummaryTable();
  summaryTable = ajaxReturnData;
  makeSummaryTable(summaryTable);
  $("#summary__table td:nth-child(1)").each(function () {
    if ($(this).text() == targetId) {
      $(this).parent().attr("id", "summary__tr").addClass("selected-record");
    }
  });
  // Scroll
  document.getElementById("summary__tr").scrollIntoView({
    behavior: "smooth",
  });
});

$(document).on("click", "#clipboard__button", function () {
  window.open(
    "./AddPNFromClipBoard.html",
    null,
    "width=720, height=530, left=280, top=200, toolbar=yes,menubar=yes,scrollbars=no"
  );
});

function getInputData() {
  let inputData = new Object();
  let category2 = $("#category2__tr").find("td").eq(0).html();
  let dt = new Date();
  // .save-dataを持っている要素から値を取り出す
  $("input.save-data").each(function (index, element) {
    inputData[$(this).attr("id")] = $(this).val();
  });
  $("select.save-data").each(function (index, element) {
    inputData[$(this).attr("id")] = $(this).val();
  });
  // 日付はYY-mm-dd形式なのでYYYY-mm-dd形式に変更
  // inputData["date__input"] = "20" + inputData["date__input"];
  // targetId を別途保存
  inputData["targetId"] = $("#summary__tr").find("td").eq(0).html();
  // production category2 id の取得
  inputData["production_category2_id"] = category2;
  // 今日の日付の取得
  inputData["updated_at"] =
    dt.getFullYear() + "-" + (dt.getMonth() + 1) + "-" + dt.getDate();

  if (inputData["circumscribed_circle"] == "") {
    inputData["circumscribed_circle"] = null;
  }
  if (inputData["hardness"] == "") {
    inputData["hardness"] = null;
  }
  return inputData;
}

$(document).on("keyup", "#production_number_sort", function () {
  $(this).val($(this).val().toUpperCase()); // 小文字を大文字に
  const text = $(this).val();

  $("#summary__table tbody").empty();

  summaryTable.forEach(function (trVal) {
    if (trVal["production_number"].includes(text, 0)) {
      var newTr = $("<tr>");
      Object.keys(trVal).forEach(function (tdVal) {
        $("<td>").html(trVal[tdVal]).appendTo(newTr);
      });
      $(newTr).appendTo("#summary__table tbody");
    }
  });

  $("#summary__table_record").html(
    $("#summary__table tbody tr").length + " items"
  );
});

$(document).on("click", "#summary__table tbody tr", function () {
  const targetId = $(this).find("td").eq(0).html();
  const category2Name = $(this).find("td").eq(2).html();
  let fileName;
  let sendData = new Object();

  $("tr.selected-record").removeClass("selected-record");
  $(this).addClass("selected-record");

  $("#summary__tr").removeAttr("id");
  $(this).attr("id", "summary__tr");

  // copy to input area for edit values
  setRecordValue2Edit(targetId);

  // Update button Activation
  $("#mode_display").html("Update Mode");
  if (checkInput()) {
    $("#update__button").prop("disabled", false);
  } else {
    $("#update__button").prop("disabled", true);
  }
  // category1 table : select and scroll
  sendData = { targetId: targetId };
  fileName = "./php/ProductionNumber/SelCate2_1.php";
  myAjax.myAjax(fileName, sendData);
  setCategory(targetId);

  $("#category1__tr").removeAttr("id");
  if (ajaxReturnData.length != 0) {
    $("#category1__table tbody tr").each(function () {
      if (
        $(this).find("td").eq(0).html() == ajaxReturnData[0]["category1_id"]
      ) {
        $(this)
          .addClass("selected-record")
          .attr("id", "category1__tr")
          .get(0)
          .scrollIntoView({
            behavior: "smooth",
          });
      }
    });
  }
  // category2 table : make table and select and scroll
  sendData = { targetId: targetId };
  fileName = "./php/ProductionNumber/SelCategory2V3.php";
  myAjax.myAjax(fileName, sendData);
  if (ajaxReturnData.length == 0) return;
  $("#category2__table tbody").empty();
  ajaxReturnData.forEach(function (trVal) {
    var newTr = $("<tr>");
    Object.keys(trVal).forEach(function (tdVal) {
      $("<td>").html(trVal[tdVal]).appendTo(newTr);
    });
    if ($(newTr).find("td").eq(1).html() == category2Name) {
      $(newTr)
        .addClass("selected-record")
        .attr("id", "category2__tr")
        .get(0)
        .scrollIntoView({
          behavior: "smooth",
        });
    }
    $(newTr).appendTo("#category2__table tbody");
  });
});

function setRecordValue2Edit(targetId) {
  const fileName = "./php/ProductionNumber/SelSelSummaryV3.php";
  let sendData = new Object();

  sendData = { targetId: targetId };
  myAjax.myAjax(fileName, sendData);
  // console.log(ajaxReturnData[0]);

  // production number
  $("#production_number")
    .val(ajaxReturnData[0]["production_number"])
    .removeClass("input-required");
  // Drawng Dept.
  if (ajaxReturnData[0]["drawn_department"] != null) {
    $("#drawn_department")
      .val(ajaxReturnData[0]["drawn_department"])
      .removeClass("input-required");
  } else {
    $("#drawn_department").val("0").addClass("input-required");
  }
  // Material
  if (ajaxReturnData[0]["billet_material_id"] != null) {
    $("#billet_material_id")
      .val(ajaxReturnData[0]["billet_material_id"])
      .removeClass("input-required");
  } else {
    $("#billet_material_id").val("0").addClass("input-required");
  }
  // Aging
  if (ajaxReturnData[0]["aging_type_id"] != null) {
    $("#aging_type_id")
      .val(ajaxReturnData[0]["aging_type_id"])
      .removeClass("input-required");
  } else {
    $("#aging_type_id").val("0").addClass("input-required");
  }
  // production_length
  if (ajaxReturnData[0]["production_length"] != null) {
    $("#production_length")
      .val(ajaxReturnData[0]["production_length"])
      .removeClass("input-required");
  } else {
    $("#production_length").val("").addClass("input-required");
  }
  // circumscribed_circle
  if (ajaxReturnData[0]["circumscribed_circle"] != "") {
    $("#circumscribed_circle")
      .val(ajaxReturnData[0]["circumscribed_circle"])
      .removeClass("input-required");
  } else {
    $("#circumscribed_circle").val("");
  }
  if (ajaxReturnData[0]["hardness"] != "") {
    $("#hardness")
      .val(ajaxReturnData[0]["hardness"])
      .removeClass("input-required");
  } else {
    $("#hardness").val("");
  }
  if (ajaxReturnData[0]["hardness_note"] != "") {
    $("#hardness_note")
      .val(ajaxReturnData[0]["hardness_note"])
      .removeClass("input-required");
  } else {
    $("#hardness_note").val("");
  }
  // specific_weight
  if (ajaxReturnData[0]["specific_weight"] != null) {
    $("#specific_weight")
      .val(ajaxReturnData[0]["specific_weight"])
      .removeClass("input-required");
  } else {
    $("#specific_weight").val("").addClass("input-required");
  }
  // cross_section_area
  if (ajaxReturnData[0]["cross_section_area"] != null) {
    $("#cross_section_area")
      .val(ajaxReturnData[0]["cross_section_area"])
      .removeClass("input-required");
  } else {
    $("#cross_section_area").val("").addClass("input-required");
  }
  // packing_quantity
  if (ajaxReturnData[0]["packing_quantity"] != "") {
    $("#packing_quantity")
      .val(ajaxReturnData[0]["packing_quantity"])
      .removeClass("input-required");
  } else {
    $("#packing_quantity").val("").addClass("input-required");
  }
  // packing_row
  if (ajaxReturnData[0]["packing_row"] != null) {
    $("#packing_row")
      .val(ajaxReturnData[0]["packing_row"])
      .removeClass("input-required");
  } else {
    $("#packing_row").val("").addClass("input-required");
  }
  // packing_column
  if (ajaxReturnData[0]["packing_column"] != null) {
    $("#packing_column")
      .val(ajaxReturnData[0]["packing_column"])
      .removeClass("input-required");
  } else {
    $("#packing_column").val("").addClass("input-required");
  }
}

function setCategory(targetId) {
  const fileName = "./php/ProductionNumber/SelCate2_1.php";
  let sendData = new Object();

  sendData = { targetId: targetId };
  myAjax.myAjax(fileName, sendData);
}

$(document).on(
  "click",
  "#summary__table tbody tr.selected-record",
  function () {
    let fileName;
    let sendData = new Object();

    fileName = "./php/ProductionNumber/SelEmploeeNumber.php";
    sendData = {
      dummy: "dummy",
    };
    // console.log(sendData);
    myAjax.myAjax(fileName, sendData);

    document.getElementById("delete__dialog").showModal();
    $("#emploee_number").val("");
  }
);

// Dialog
$(document).on("click", "#dialog-cancel__button", function () {
  document.getElementById("delete__dialog").close();
  $("#update__button").prop("disabled", true);
  $("#dialog-delete__button").attr("disabled", true);
});

$(document).on("click", "#dialog-delete__button", function () {
  let fileName;
  let sendData = new Object();

  // console.log($("#summary__tr td").eq(0).text());

  fileName = "./php/ProductionNumber/DelSummary.php";
  sendData = {
    targetId: $("#summary__tr td").eq(0).text(),
  };
  myAjax.myAjax(fileName, sendData);
  document.getElementById("delete__dialog").close();
  readSummaryTable();
  $("#dialog-delete__button").attr("disabled", true);

  // Clear input value
  $("input.need-clear").val("").addClass("input-required");
  $("select.need-clear").val("0").addClass("input-required");
  $("#mode_display").html("");

  $("#update__button").prop("disabled", true);
});

$(document).on("keyup", "#emploee_number", function () {
  if (
    $(this).val().length == 7 &&
    findValueInObject(ajaxReturnData, $(this).val())
  ) {
    $("#dialog-delete__button").attr("disabled", false);
  }
});

// summary table sort  part from ChatGPT
$(document).on("click", "#summary__table th.sort", function () {
  let header = $(this);
  let order = header.data("sort-order");
  let column_cnt = $(this).index();

  if (column_cnt == column) {
    sameColumn = true;
    if (sortReverse == true) {
      sortReverse = false;
    } else {
      sortReverse = true;
    }
  } else {
    sameColumn = false;
    sortReverse = false;
  }
  column = column_cnt;

  if (order === undefined || order === "desc") {
    header.data("sort-order", "asc");
    sortTable(table, header.index());
    // クリックした列のヘッダーに昇順を示す矢印を表示
    header.find("i").remove();
    header.append('<i class="fas fa-sort-up ml-1"></i>');
  } else {
    header.data("sort-order", "desc");
    sortTable(table, header.index());
    // クリックした列のヘッダーに降順を示す矢印を表示
    header.find("i").remove();
    header.append('<i class="fas fa-sort-down ml-1"></i>');
  }
  displayArrowMark(header);
});

function sortTable(table, column) {
  var rows = table.find("tr:gt(0)").toArray().sort(comparer(column));

  if (sortReverse) {
    // 降順にソートする場合は以下のコメントを解除
    rows = rows.reverse();
  }

  for (var i = 0; i < rows.length; i++) {
    table.append(rows[i]);
  }
}

// ソート用の比較関数を定義
function comparer(column) {
  return function (a, b) {
    var valA = getCellValue(a, column);
    var valB = getCellValue(b, column);
    return $.isNumeric(valA) && $.isNumeric(valB)
      ? valA - valB
      : valA.localeCompare(valB);
  };
}

// セルの値を取得する関数を定義
function getCellValue(row, column) {
  return $(row).children("td").eq(column).text();
}

// display function for arrow mark
function displayArrowMark(header) {
  if (sameColumn) {
    if (sortReverse) {
      header.find("img").attr("src", "./img/arrow_up.png");
      // .attr("id", "table_arrow__img");
    } else {
      header.find("img").attr("src", "./img/arrow_down.png");
      // .attr("id", "table_arrow__img");
    }
  } else {
    $("#table_arrow__img").remove();
    header
      .find("div.sort_img")
      .append(
        $("<img>")
          .attr("src", "./img/arrow_up.png")
          .attr("id", "table_arrow__img")
      );
  }
}

function findValueInObject(obj, searchValue) {
  for (let key in obj) {
    if (
      obj[key]["emploee_number"] == searchValue &&
      obj[key]["position_id"] == 1
    ) {
      return true;
    }
  }
  return false;
}

$(document).on("click", "#language__mark", function () {
  const str = $("#language__mark").attr("src");
  const language = str.match(/\/([^.\/]+)\.\w+$/);
  const tileLettersObject = $("div.title__letters");
  // console.log(tileLettersObject);
  let fileName;
  let sendData = new Object();

  fileName = "./php/ProductionNumber/SelTitleName.php";
  sendData = {
    dummy: "dummy",
  };
  myAjax.myAjax(fileName, sendData);

  tileLettersObject.each(function () {
    let targetObj = $(this);
    ajaxReturnData.forEach(function (databaseLetters) {
      // console.log(targetObj.text() + "\n" + databaseLetters["english"]);
      switch (language[1]) {
        case "En":
          if (targetObj.text() == databaseLetters["english"]) {
            // console.log(
            //   databaseLetters["english"] + " : " + databaseLetters["vietnamese"]
            // );
            targetObj.text(databaseLetters["vietnamese"]);
            $("#language__mark").attr("src", "./img/Vn.png");
          }
          break;
        case "Vn":
          if (targetObj.text() == databaseLetters["vietnamese"]) {
            // console.log(
            //   databaseLetters["english"] + " : " + databaseLetters["vietnamese"]
            // );
            targetObj.text(databaseLetters["english"]);
            $("#language__mark").attr("src", "./img/En.png");
          }
          break;
      }
    });
  });
});

$(document).on("click", "#edit_category__button", function () {
  window.open(
    "./CategoryNameEdit.html",
    null,
    "width=720, height=530, left=280, top=200, toolbar=yes,menubar=yes,scrollbars=no"
  );
});

$(document).on("click", "#copyToClipboard__button", function () {
  var targetTable = $("#summary__table");
  var strTable = convertHTMLTableToStrTable(targetTable);
  // console.log(strTable);

  navigator.clipboard
    .writeText(strTable)
    .then(function () {
      console.log("Text copied to clipboard!");
    })
    .catch(function (err) {
      console.error("Failed to copy text: ", err);
    });
});

function convertHTMLTableToStrTable(targetTable) {
  var strTable = "";
  var rows = targetTable.find("tr");

  rows.each(function () {
    var cells = $(this).find("td, th");
    cells.each(function () {
      var text = $(this).text().trim();
      strTable += text + "\t";
    });
    strTable += "\n";
  });

  return strTable;
}

// $(document).on("click", "#test__button", function () {
//   $("#summary__table tbody").empty();
// });
