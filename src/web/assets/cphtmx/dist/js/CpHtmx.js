// Anytime Htmx does a server request, add standard Craft headers (includes CSRF)
htmx.on('htmx:configRequest', function(evt) {
    evt.detail.headers = {...evt.detail.headers, ...Craft._actionHeaders()};
});

// Anytime Htmx does a swap, look for html in templates to be added to head or foot in CP
htmx.on('htmx:load', function(evt) {

    const content = evt.detail.elt;

    const headHtmls = content.querySelectorAll("template.hx-head-html");
    const bodyHtmls = content.querySelectorAll("template.hx-body-html");

    for (var i = 0; i < headHtmls.length; i++) {
        var headHtml = headHtmls[i].innerHTML;
        if (headHtml) {
            Craft.appendHeadHtml(headHtml);
        }
    }

    for (var i = 0; i < bodyHtmls.length; i++) {
        var bodyHtml = bodyHtmls[i].innerHTML;
        if (bodyHtml) {
            Craft.appendFootHtml(bodyHtml);
        }
    }

    Craft.initUiElements(content);
});