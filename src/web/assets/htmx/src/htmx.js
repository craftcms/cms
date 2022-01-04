// Anytime Htmx does a server request, add standard Craft headers (includes CSRF)
htmx.on('htmx:configRequest', function(evt) {
    evt.detail.headers = {...evt.detail.headers, ...Craft._actionHeaders()};
});

// Anytime Htmx does a swap, look for html in templates to be added to head or foot in CP
htmx.on('htmx:load', function(evt) {
    if (evt.detail.elt === document.body) {
        return;
    }

    const headHtmls = evt.detail.elt.querySelectorAll("template.hx-head-html");
    const bodyHtmls = evt.detail.elt.querySelectorAll("template.hx-body-html");

    for (let i = 0; i < headHtmls.length; i++) {
        const headHtml = headHtmls[i].innerHTML;
        if (headHtml) {
            Craft.appendHeadHtml(headHtml);
        }
    }

    for (let i = 0; i < bodyHtmls.length; i++) {
        const bodyHtml = bodyHtmls[i].innerHTML;
        if (bodyHtml) {
            Craft.appendBodyHtml(bodyHtml);
        }
    }

    Craft.initUiElements(evt.detail.elt);
});
