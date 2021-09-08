document.body.addEventListener('htmx:configRequest', function(evt) {
    evt.detail.headers = {...evt.detail.headers, ...Craft._actionHeaders()};
});

window.addEventListener("load", function() {

    htmx.onLoad(function(content) {

        console.log('HTMX onLoad');

        const sortables = content.querySelectorAll(".sortable");
        for (var i = 0; i < sortables.length; i++) {
            var sortable = sortables[i];
            new Sortable(sortable, {
                animation: 150,
                draggable: '.draggable',
                handle: '.draggable-handle',
                direction: 'vertical',
            });
        }

        const headHtml = content.querySelector('#head-html').innerHTML;
        const footHtml = content.querySelector('#foot-html').innerHTML;
        if (headHtml) {
            Craft.appendHeadHtml(headHtml);
        }
        if (footHtml) {
            Craft.appendFootHtml(footHtml);
        }
    });
});