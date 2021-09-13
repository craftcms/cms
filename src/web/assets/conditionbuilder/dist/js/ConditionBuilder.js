htmx.on('htmx:load', function(evt) {
    const content = evt.detail.elt;
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
});
