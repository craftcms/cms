htmx.on('htmx:load', function(evt) {
    const content = evt.detail.elt;
    const sortables = content.querySelectorAll(".sortable");
    for (let i = 0; i < sortables.length; i++) {
        const sortable = sortables[i];
        new Sortable(sortable, {
            animation: 150,
            draggable: '.draggable',
            handle: '.draggable-handle',
            direction: 'vertical',
        });
    }
});
