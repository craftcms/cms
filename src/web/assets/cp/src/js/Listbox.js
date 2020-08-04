/** global: Craft */
/** global: Garnish */
/**
 * Listbox
 */
Craft.Listbox = Garnish.Base.extend({
    $container: null,
    $options: null,
    $selectedOption: null,
    selectedOptionIndex: null,

    init: function(container, settings) {
        this.$container = $(container);
        this.setSettings(settings, Craft.Listbox.defaults);

        // Is this already a listbox?
        if (this.$container.data('listbox')) {
            Garnish.log('Double-instantiating a listbox on an element');
            this.$container.data('listbox').destroy();
        }

        this.$container.data('listbox', this);
        this.$options = this.$container.find('[role=option]');

        // is there already a selected option?
        this.$selectedOption = this.$options.filter('[aria-selected=true]');
        if (this.$selectedOption.length) {
            this.selectedOptionIndex = this.$options.index(this.$selectedOption);
        } else {
            this.$selectedOption = null;
        }

        this.addListener(this.$container, 'keydown', ev => {
            switch (ev.keyCode) {
                case Garnish.UP_KEY:
                    this.selectPrev();
                    ev.preventDefault();
                    break;
                case Garnish.DOWN_KEY:
                    this.selectNext();
                    ev.preventDefault();
                    break;
                case Garnish.LEFT_KEY:
                    if (Craft.orientation === 'ltr') {
                        this.selectPrev();
                    } else {
                        this.selectNext();
                    }
                    ev.preventDefault();
                    break;
                case Garnish.RIGHT_KEY:
                    if (Craft.orientation === 'ltr') {
                        this.selectNext();
                    } else {
                        this.selectPrev();
                    }
                    ev.preventDefault();
                    break;
            }
        });

        this.addListener(this.$options, 'click', ev => {
            this.select(this.$options.index($(ev.currentTarget)));
            ev.preventDefault();
        });
    },

    select: function(index) {
        if (index < 0 || index >= this.$options.length || index === this.selectedOptionIndex) {
            return;
        }

        this.$selectedOption
            .removeClass(this.settings.selectedClass)
            .attr('aria-selected', 'false');

        this.$selectedOption = this.$options.eq(index)
            .addClass(this.settings.selectedClass)
            .attr('aria-selected', 'true');

        this.selectedOptionIndex = index;

        this.settings.onChange(this.$selectedOption, index);
        this.trigger('change', {
            $selectedOption: this.$selectedOption,
            selectedOptionIndex: index,
        });
    },

    selectPrev: function() {
        if (this.selectedOptionIndex === null) {
            this.select(0);
        } else {
            this.select(this.selectedOptionIndex - 1);
        }
    },

    selectNext: function() {
        if (this.selectedOptionIndex === null) {
            this.select(0);
        } else {
            this.select(this.selectedOptionIndex + 1);
        }
    },
}, {
    defaults: {
        selectedClass: 'active',
        focusClass: 'focus',
        onChange: $.noop,
    }
});
