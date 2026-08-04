/** global: Craft */
/** global: Garnish */
/**
 * Cards Element Index View
 */
Craft.CardsElementIndexView = Craft.BaseElementIndexView.extend({
  cardSort: null,

  afterInit: function () {
    // Create the table sorter, but only if the device has mouse events
    if (this.settings.sortable && Craft.hasMousePointerEvents()) {
      this.cardSort = new Garnish.DragSort(this.getAllElements(), {
        container: this.$elementContainer,
        filter: this.settings.selectable
          ? () => {
              // Only return all the selected items if the target item is selected
              if (
                this.cardSort.$targetItem.children('.element').hasClass('sel')
              ) {
                return this.elementSelect.getSelectedItems().parent('li');
              } else {
                return this.cardSort.$targetItem;
              }
            }
          : null,
        ignoreHandleSelector: null,
        handle:
          '> .element > .card-titlebar > .card-actions-container > .card-actions > .move-btn',
        collapseDraggees: true,
        magnetStrength: 4,
        helperLagBase: 1.5,
        helper: ($helper) => {
          $helper.children().outerHeight(this.cardSort.$draggee.height());
          return $helper;
        },
        onInsertionPointChange: () => {
          for (let $helper of this.cardSort.helpers) {
            $helper.children().outerHeight(this.cardSort.$draggee.height());
          }
        },
        onSortChange: () => {
          if (this.settings.selectable) {
            this.elementSelect.resetItemOrder();
          }
          this.settings.onSortChange(this.cardSort.$draggee);
        },
      });
    } else {
      $(
        '.element > .card-titlebar > .card-actions-container > .card-actions > .move-btn'
      ).hide();
    }
  },

  getElementContainer: function () {
    return this.$container.find('> .card-grid');
  },
});
