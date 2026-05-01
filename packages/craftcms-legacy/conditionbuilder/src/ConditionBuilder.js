htmx.on('htmx:load', function (evt) {
  if (evt.detail.elt === document.body) {
    return;
  }

  const container = evt.detail.elt.querySelector('.condition');
  if (container && container.classList.contains('sortable')) {
    const sortItems = container.querySelectorAll('.condition-rule');
    if (sortItems.length) {
      new Garnish.DragSort(sortItems, {
        axis: Garnish.Y_AXIS,
        handle: '.draggable-handle',
      });
    }
  }
});
