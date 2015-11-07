function addBookmark(trade) {
  $.ajax({
    type: "POST",
    url: "core/addbookmark.php",
    data: "trade="+trade
  });
}

function removeBookmark(trade) {
  $.ajax({
    type: "POST",
    url: "core/removebookmark.php",
    data: "trade="+trade
  });
}