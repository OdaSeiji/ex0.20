$(document).on("click", "#stock-add__button", function () {
  //
  const editRow = $("#billet-stocks__table tbody tr.input-record");
  // const summaryRow = $("#summary__table tr.selected-record");
  // const billetSize = summaryRow.find("td:eq(6)").html();
  const billetSize = 12;
  // const billetLength = summaryRow.find("td:eq(7)").html();
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
          <select>
            <option value=0>-</option>
            <option value=9>9</option>
            <option value=12>12</option>
            <option value=14>14</option>
          </select>
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

  console.log("hello");
});
