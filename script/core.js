function livePreview(trade, that) {
  $("#preview").html('<img src="../img/load.gif" id="loading" style="margin: 0.75em 2%">');
  $("#preview").css('marginTop', that.position().top - 90 );
  $.ajax({
    url: 'ajax/livePreview.php',
    type: 'POST',
    data: "t="+trade,
    success: function(data) {
      $("#preview").html(data).slideDown('fast');
    }
  });
}

function ajaxLoad(where, what) {
  $(where).html('<img src="../img/load.gif" id="loading" style="margin: 0.75em 2%">');
  $.ajax({
    url: 'ajax/'+what+'.php',
    type: 'POST',
    success: function(data) {
      $(where).html(data).slideDown('fast');
    }
  });
}

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

function removeQueue() {
	$.ajax({
		url: "ajax/removeQueue.php",
		success: function(data) {
			if (data) {
				window.alert(data);
			} else {
				location.reload();
			}
		}
	});
}

function setLanguage(lang) {
  $.ajax({
    type: "POST",
    url: "ajax/setLanguage.php",
    data: "lang="+lang,
	success: function(data) {
		location.reload();
	}
  });
}

function choseStream(match,lang) {
  $.ajax({
    type: "POST",
    url: "ajax/choseStream.php",
    data: "m="+match+"&lang="+lang,
	success: function(data) {
		$("#stream").html(data);
	}
  });
}

function choseVOD(match,lang) {
  $.ajax({
    type: "POST",
    url: "ajax/choseVOD.php",
    data: "m="+match+"&lang="+lang,
	success: function(data) {
		$("#youtube").html(data);
	}
  });
}

function previewItem(that) {
    var newSrc = that.parents(".oitm").find(".smallimg").attr("src").replace("99fx66f", "512fx388f");
    $("#modalImg").attr("src", newSrc);
    $("#modalPreview").fadeIn("fast");
}

function changeSteamOfferURL(changeOnly, bets) {
    bets = typeof bets !== 'undefined' ? bets : false;
    
    var change = "";
    var forbets = "";
    var betsid = "";
    if (bets) {
        forbets = "&bets=1";
        betsid = "bets";
    }
    if ($("#steamoffers"+betsid).val() != null && changeOnly == true) {
        var str = $("#steamoffers"+betsid).val();
        var n = str.indexOf("token=");
        if (n != -1) change = str.substring(n+6,n+14);
    }

    $.ajax({
        type: "POST",
        url: "ajax/changeSOURL.php",
        data: 'steamoffers=' + change + forbets,
        success: function(data) {
            window.location.href = location.href;
        }
    });
}