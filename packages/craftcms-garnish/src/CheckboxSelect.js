/** global: Garnish */
/**
 * Checkbox select class
 */
Garnish.CheckboxSelect = Garnish.Base.extend(
    {
        $container: null,
        $all: null,
        $options: null,

        init: function(container) {
            this.$container = $(container);

            // Is this already a checkbox select?
            if (this.$container.data('checkboxSelect')) {
                Garnish.log('Double-instantiating a checkbox select on an element');
                this.$container.data('checkbox-select').destroy();
            }

            this.$container.data('checkboxSelect', this);

            var $checkboxes = this.$container.find('input');
            this.$all = $checkboxes.filter('.all:first');
            this.$options = $checkboxes.not(this.$all);

            this.addListener(this.$all, 'change', 'onAllChange');
        },

        onAllChange: function() {
            var isAllChecked = this.$all.prop('checked');

            this.$options.prop({
                checked: isAllChecked,
                disabled: isAllChecked
            });
        },

        /**
         * Destroy
         */
        destroy: function() {
            this.$container.removeData('checkboxSelect');
            this.base();
        }
    }
);
