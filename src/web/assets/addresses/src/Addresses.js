htmx.on('htmx:load', function(evt) {
    if (evt.detail.elt === document.body) {
        return;
    }
});
