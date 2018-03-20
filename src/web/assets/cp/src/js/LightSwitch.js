/** global: Craft */
/** global: Garnish */
/**
 * Light Switch
 */
Craft.LightSwitch = Garnish.Base.extend(
    {
        settings: null,
        $outerContainer: null,
        $innerContainer: null,
        $input: null,
        small: false,
        on: null,
        dragger: null,

        dragStartMargin: null,

        init: function(outerContainer, settings) {
            this.$outerContainer = $(outerContainer);

            // Is this already a lightswitch?
            if (this.$outerContainer.data('lightswitch')) {
                Garnish.log('Double-instantiating a lightswitch on an element');
                this.$outerContainer.data('lightswitch').destroy();
            }

            this.$outerContainer.data('lightswitch', this);

            this.small = this.$outerContainer.hasClass('small');

            this.setSettings(settings, Craft.LightSwitch.defaults);

            this.$innerContainer = this.$outerContainer.find('.lightswitch-container:first');
            this.$input = this.$outerContainer.find('input:first');

            // If the input is disabled, go no further
            if (this.$input.prop('disabled')) {
                return;
            }

            this.on = this.$outerContainer.hasClass('on');

            this.$outerContainer.attr({
                'role': 'checkbox',
                'aria-checked': (this.on ? 'true' : 'false')
            });

            this.addListener(this.$outerContainer, 'mousedown', '_onMouseDown');
            this.addListener(this.$outerContainer, 'keydown', '_onKeyDown');

            this.dragger = new Garnish.BaseDrag(this.$outerContainer, {
                axis: Garnish.X_AXIS,
                ignoreHandleSelector: null,
                onDragStart: $.proxy(this, '_onDragStart'),
                onDrag: $.proxy(this, '_onDrag'),
                onDragStop: $.proxy(this, '_onDragStop')
            });
        },

        turnOn: function() {
            this.$outerContainer.addClass('dragging');

            var animateCss = {};
            animateCss['margin-' + Craft.left] = 0;
            this.$innerContainer.velocity('stop').velocity(animateCss, Craft.LightSwitch.animationDuration, $.proxy(this, '_onSettle'));

            this.$input.val(this.settings.value);
            this.$outerContainer.addClass('on');
            this.$outerContainer.attr('aria-checked', 'true');

            if (this.on !== (this.on = true)) {
                this.onChange();
            }
        },

        turnOff: function() {
            this.$outerContainer.addClass('dragging');

            var animateCss = {};
            animateCss['margin-' + Craft.left] = this._getOffMargin();
            this.$innerContainer.velocity('stop').velocity(animateCss, Craft.LightSwitch.animationDuration, $.proxy(this, '_onSettle'));

            this.$input.val('');
            this.$outerContainer.removeClass('on');
            this.$outerContainer.attr('aria-checked', 'false');

            if (this.on !== (this.on = false)) {
                this.onChange();
            }
        },

        toggle: function(event) {
            if (!this.on) {
                this.turnOn();
            }
            else {
                this.turnOff();
            }
        },

        onChange: function() {
            this.trigger('change');
            this.settings.onChange();
            this.$outerContainer.trigger('change');
        },

        _onMouseDown: function() {
            this.addListener(Garnish.$doc, 'mouseup', '_onMouseUp');
        },

        _onMouseUp: function() {
            this.removeListener(Garnish.$doc, 'mouseup');

            // Was this a click?
            if (!this.dragger.dragging) {
                this.toggle();
            }
        },

        _onKeyDown: function(event) {
            switch (event.keyCode) {
                case Garnish.SPACE_KEY: {
                    this.toggle();
                    event.preventDefault();
                    break;
                }
                case Garnish.RIGHT_KEY: {
                    if (Craft.orientation === 'ltr') {
                        this.turnOn();
                    }
                    else {
                        this.turnOff();
                    }

                    event.preventDefault();
                    break;
                }
                case Garnish.LEFT_KEY: {
                    if (Craft.orientation === 'ltr') {
                        this.turnOff();
                    }
                    else {
                        this.turnOn();
                    }

                    event.preventDefault();
                    break;
                }
            }
        },

        _getMargin: function() {
            return parseInt(this.$innerContainer.css('margin-' + Craft.left));
        },

        _onDragStart: function() {
            this.$outerContainer.addClass('dragging');
            this.dragStartMargin = this._getMargin();
        },

        _onDrag: function() {
            var margin;

            if (Craft.orientation === 'ltr') {
                margin = this.dragStartMargin + this.dragger.mouseDistX;
            }
            else {
                margin = this.dragStartMargin - this.dragger.mouseDistX;
            }

            if (margin < this._getOffMargin()) {
                margin = this._getOffMargin();
            }
            else if (margin > 0) {
                margin = 0;
            }

            this.$innerContainer.css('margin-' + Craft.left, margin);
        },

        _onDragStop: function() {
            var margin = this._getMargin();

            if (margin > (this._getOffMargin() / 2)) {
                this.turnOn();
            }
            else {
                this.turnOff();
            }
        },

        _onSettle: function() {
            this.$outerContainer.removeClass('dragging');
        },

        destroy: function() {
            this.base();
            this.dragger.destroy();
        },

        _getOffMargin: function() {
            return (this.small ? -9 : -11);
        }

    }, {
        animationDuration: 100,
        defaults: {
            value: '1',
            onChange: $.noop
        }
    });
