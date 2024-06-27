/** global: Craft */
/** global: Garnish */
/** global: $ */
/** global: jQuery */

Craft.CardViewDesigner = Garnish.Base.extend(
  {
    $container: null,

    init: function (container, settings) {
      this.$container = $(container);
      this.setSettings(settings, Craft.CardViewDesigner.defaults);

      const sortItems = this.$container.find('.draggable');
      if (sortItems.length) {
        new Garnish.DragSort(sortItems, {
          axis: Garnish.Y_AXIS,
          handle: '.draggable-handle',
        });
      }
    },
  },
  {
    defaults: {},
  }
);
