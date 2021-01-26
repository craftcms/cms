/** global: Craft */
/** global: Garnish */
/**
 * Thumb Element Index View
 */
Craft.ThumbsElementIndexView = Craft.BaseElementIndexView.extend({
    getElementContainer: function() {
        return this.$container.children('ul');
    }
});
