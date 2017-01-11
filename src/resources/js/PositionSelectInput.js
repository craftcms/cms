(function($) {
    /** global: Craft */
    /** global: Garnish */
    PositionSelectInput = Garnish.Base.extend({

        $container: null,
        $options: null,
        $selectedOption: null,
        $input: null,

        init: function(id) {
            this.$container = $('#' + id);
            this.$options = this.$container.find('.btn');
            this.$selectedOption = this.$options.filter('.active');
            this.$input = this.$container.next('input');

            this.addListener(this.$options, 'click', 'onOptionSelect');
        },

        onOptionSelect: function(ev) {
            var $option = $(ev.currentTarget);

            if ($option.hasClass('active')) {
                return;
            }

            this.$selectedOption.removeClass('active');
            this.$selectedOption = $option.addClass('active');
            this.$input.val($option.data('option'));
        }

    });
})(jQuery);
