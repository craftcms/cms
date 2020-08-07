(function($) {
    /** global: Craft */
    /** global: Garnish */
    /**
     * Slide Picker
     */
    Craft.SlidePicker = Garnish.Base.extend({
        min: null,
        max: null,
        totalSteps: null,
        value: null,

        $container: null,
        $buttons: null,

        init: function(value, settings) {
            this.setSettings(settings, Craft.SlidePicker.defaults);

            this.$container = $('<div/>', {
                class: 'slide-picker',
                role: 'slider',
                tabindex: 0,
            });

            this.refresh();
            this.setValue(value, false);

            this.addListener(this.$container, 'keydown', ev => {
                switch (ev.keyCode) {
                    case Garnish.UP_KEY:
                        this.setValue(this.value + this.settings.step);
                        ev.preventDefault();
                        break;
                    case Garnish.DOWN_KEY:
                        this.setValue(this.value - this.settings.step);
                        ev.preventDefault();
                        break;
                    case Garnish.RIGHT_KEY:
                        if (Craft.orientation === 'ltr') {
                            this.setValue(this.value + this.settings.step);
                        } else {
                            this.setValue(this.value - this.settings.step);
                        }
                        ev.preventDefault();
                        break;
                    case Garnish.LEFT_KEY:
                        if (Craft.orientation === 'ltr') {
                            this.setValue(this.value - this.settings.step);
                        } else {
                            this.setValue(this.value + this.settings.step);
                        }
                        ev.preventDefault();
                        break;
                }
            });
        },

        refresh: function() {
            // Figure out what the min/max values are
            this.min = this._min();
            this.max = this._max();
            this.totalSteps = (this.max - this.min) / this.settings.step;

            if (!Number.isInteger(this.totalSteps)) {
                throw 'Invalid SlidePicker config';
            }

            if (this.$buttons) {
                this.$buttons.remove();
            }

            this.$container.attr('aria-valuemin', this.min);
            this.$container.attr('aria-valuemax', this.max);
            this.$buttons = $();

            // Create the buttons
            for (let value = this.min; value <= this.max; value += this.settings.step) {
                this.$buttons = this.$buttons.add($('<a/>', {
                    title: this.settings.valueLabel(value),
                    data: {value: value}
                }));
            }

            this.$buttons.appendTo(this.$container);

            if (this.value !== null) {
                let value = this.value;
                this.value = null;
                this.setValue(value, false);
            }

            this.addListener(this.$buttons, 'mouseover', ev => {
                this.$buttons.removeClass('active-hover last-active-hover');
                $(ev.currentTarget)
                    .addClass('active-hover last-active-hover')
                    .prevAll().addClass('active-hover');
            });

            this.addListener(this.$buttons, 'mouseout', () => {
                this.$buttons.removeClass('active-hover');
            });

            this.addListener(this.$buttons, 'click', ev => {
                this.setValue($.data(ev.currentTarget, 'value'));
                ev.stopPropagation();
                this.$container.focus();
            });
        },

        setValue: function(value, triggerEvent) {
            value = Math.max(Math.min(value, this.max), this.min);

            if (this.value === (this.value = value)) {
                return;
            }

            this.$container.attr({
                'aria-valuenow': this.value,
                'aria-valuetext': this.settings.valueLabel(this.value),
            });

            this.$buttons.removeClass('last-active active');
            let $activeButton = this.$buttons.eq((this.value - this.min) / this.settings.step);
            $activeButton.add($activeButton.prevAll()).addClass('active');
            $activeButton.addClass('last-active');

            if (triggerEvent !== false) {
                this.settings.onChange(value);
            }
        },

        _min: function() {
            if (typeof this.settings.min === 'function') {
                return this.settings.min();
            }
            return this.settings.min;
        },

        _max: function() {
            if (typeof this.settings.max === 'function') {
                return this.settings.max();
            }
            return this.settings.max;
        },
    }, {
        defaults: {
            min: 0,
            max: 100,
            step: 10,
            valueLabel: null,
            onChange: $.noop,
        }
    });
})(jQuery);
