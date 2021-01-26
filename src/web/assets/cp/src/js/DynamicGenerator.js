/** global: Craft */
/** global: Garnish */
/**
 * Handle Generator
 */
Craft.DynamicGenerator = Craft.BaseInputGenerator.extend({
    callback: $.noop,

    init: function(source, target, callback) {
        this.callback = callback;
        this.base(source, target);
    },

    generateTargetValue: function(sourceVal) {
        return this.callback(sourceVal);
    }
});
