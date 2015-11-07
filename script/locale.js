if (localStorage.getItem("cookiepolicy") != 1) {
    var cookietxt = localStorage.getItem("cookietxt");
    if (typeof(cookietxt) == "string") {
        $("body").append("<div class=\"announce\" id=\"cookiepolicy\">"+localStorage.getItem("cookietxt")+"</div>");
    } else {
        $.getJSON( "http://csgolounge.net/localize_csgl", function( json ) {
            document.cookie="country="+json.country;
            localStorage.setItem("country", json.country);
            if (json.eu == false) { localStorage.setItem("cookiepolicy", 1); }
            else { $("body").append("<div class=\"announce\" id=\"cookiepolicy\">"+json.text+"</div>"); }
        }).fail(function() {
            $("body").append("<div class=\"announce\" id=\"cookiepolicy\">We use cookies to personalise content and ads, to provide social media features and to analyse our traffic. We also share information about your use of our site with our social media, advertising and analytics partners. <a href=\"/legal\">See details</a>.<br><a class=\"buttonright\" onclick=\"acceptCookiePolicy()\">Accept</a></div>");
        });
    }
}

if (!localStorage.getItem("country")) {
    $.getJSON( "http://csgolounge.net/localize_csgl", function( json ) {
        document.cookie="country="+json.country;
        localStorage.setItem("country", json.country);
    });
}

function acceptCookiePolicy() {
    localStorage.setItem("cookiepolicy", 1);
    localStorage.removeItem("cookietxt");
    $("#cookiepolicy").slideUp();
}