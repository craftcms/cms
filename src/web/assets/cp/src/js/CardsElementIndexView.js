/** global: Craft */
/** global: Garnish */
/**
 * Cards Element Index View
 */
Craft.CardsElementIndexView = Craft.BaseElementIndexView.extend({
  cardSort: null,

  afterInit: function () {
    // Create the table sorter
    if (this.settings.sortable) {
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
        handle: '> .element > .card-actions-container > .card-actions > .move',
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
    }
  },

  getElementContainer: function () {
    return this.$container.find('> .card-grid');
  },
});
