/** global: Craft */
/** global: Garnish */
/**
 * Cards Element Index View
 */
Craft.CardsElementIndexView = Craft.BaseElementIndexView.extend({
  getElementContainer: function () {
    return this.$container.find('> .card-grid');
  },

  getSelectHandle: function () {
    return null;
  },
});
