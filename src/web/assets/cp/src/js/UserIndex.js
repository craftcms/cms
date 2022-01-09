/** global: Craft */
/** global: Garnish */
/**
 * User index class
 */
Craft.UserIndex = Craft.BaseElementIndex.extend({
    init: function(elementType, $container, settings) {
        this.on('selectSource', this.updateButton.bind(this));
        this.base(elementType, $container, settings);
    },
    updateButton: function() {
        if (!this.$source) {
            return;
        }

        // Get the handle of the selected source
        var sourceHandle = this.$source.data('key');

        // Update the URL if we're on the Categories index
        // ---------------------------------------------------------------------

        if (this.settings.context === 'index' && typeof history !== 'undefined') {
            var uri = 'users';

            if (sourceHandle && sourceHandle !== '*') {
                uri += '/' + sourceHandle;
            }

            history.replaceState({}, '', Craft.getUrl(uri));
        }
    },
});

// Register it!
Craft.registerElementIndexClass('craft\\elements\\User', Craft.UserIndex);
