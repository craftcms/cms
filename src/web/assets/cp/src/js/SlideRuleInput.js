/** global: Garnish */

Craft.SlideRuleInput = Garnish.Base.extend({

    $container: null,
    $options: null,
    $selectedOption: null,
    $input: null,
    value: null,

    startPositionX: null,

    init: function(id, settings) {
        this.setSettings(settings, Craft.SlideRuleInput.defaultSettings);

        this.value = 0;
        this.graduationsMin = -70;
        this.graduationsMax = 70;
        this.slideMin = -45;
        this.slideMax = 45;

        this.$container = $('#' + id);
        this.$overlay = $('<div class="overlay"></div>').appendTo(this.$container);
        this.$cursor = $('<div class="cursor"></div>').appendTo(this.$container);
        this.$graduations = $('<div class="graduations"></div>').appendTo(this.$container);
        this.$graduationsUl = $('<ul></ul>').appendTo(this.$graduations);

        for (var i = this.graduationsMin; i <= this.graduationsMax; i++) {
            var $li = $('<li class="graduation" data-graduation="' + i + '"><div class="label">' + i + '</div></li>').appendTo(this.$graduationsUl);

            if ((i % 5) === 0) {
                $li.addClass('main-graduation');
            }

            if (i === 0) {
                $li.addClass('selected');
            }
        }

        this.$options = this.$container.find('.graduation');

        this.addListener(this.$container, 'resize', $.proxy(this, '_handleResize'));
        this.addListener(this.$container, 'tapstart', $.proxy(this, '_handleTapStart'));
        this.addListener(Garnish.$bod, 'tapmove', $.proxy(this, '_handleTapMove'));
        this.addListener(Garnish.$bod, 'tapend', $.proxy(this, '_handleTapEnd'));

        // Set to zero

        // this.setValue(0);

        setTimeout($.proxy(function() {
            // (n -1) options because the border is placed on the left of the 10px box
            this.graduationsCalculatedWidth = (this.$options.length - 1) * 10;
            this.$graduationsUl.css('left', (-this.graduationsCalculatedWidth / 2) + this.$container.width() / 2);
        }, this), 50);
    },

    _handleResize: function() {
        var left = this.valueToPosition(this.value);
        this.$graduationsUl.css('left', left);
    },

    _handleTapStart: function(ev, touch) {
        ev.preventDefault();

        this.startPositionX = touch.position.x;
        this.startLeft = this.$graduationsUl.position().left;

        this.dragging = true;
        this.onStart();
    },

    _handleTapMove: function(ev, touch) {
        if (this.dragging) {
            ev.preventDefault();

            var curX = this.startPositionX - touch.position.x;
            var left = this.startLeft - curX;
            var value = this.positionToValue(left);

            this.setValue(value);

            this.onChange();
        }
    },

    setValue: function(value) {
        var left = this.valueToPosition(value);
        if (value < this.slideMin) {
            value = this.slideMin;
            left = this.valueToPosition(value);

        }
        else if (value > this.slideMax) {
            value = this.slideMax;
            left = this.valueToPosition(value);
        }

        this.$graduationsUl.css('left', left);

        if (value >= this.slideMin && value <= this.slideMax) {
            this.$options.removeClass('selected');

            $.each(this.$options, function(key, option) {
                if ($(option).data('graduation') > 0) {
                    if ($(option).data('graduation') <= value) {
                        $(option).addClass('selected');
                    }
                }
                if ($(option).data('graduation') < 0) {
                    if ($(option).data('graduation') >= value) {
                        $(option).addClass('selected');
                    }
                }

                if ($(option).data('graduation') == 0) {
                    $(option).addClass('selected');
                }
            });
        }

        this.value = value;
    },

    _handleTapEnd: function(ev) {
        if (this.dragging) {
            ev.preventDefault();
            this.dragging = false;
            this.onEnd();
        }
    },

    positionToValue: function(position) {
        var scaleMin = (this.graduationsMin * -1);
        var scaleMax = (this.graduationsMin - this.graduationsMax) * -1;

        return (( ( this.$graduations.width() / 2 ) + (position * -1) ) / this.graduationsCalculatedWidth) * scaleMax - scaleMin;
    },

    valueToPosition: function(value) {
        var scaleMin = (this.graduationsMin * -1);
        var scaleMax = (this.graduationsMin - this.graduationsMax) * -1;

        return -((value + scaleMin) * this.graduationsCalculatedWidth / scaleMax - this.$graduations.width() / 2);
    },

    onStart: function() {
        if (typeof this.settings.onChange === 'function') {
            this.settings.onStart(this);
        }
    },

    onChange: function() {
        if (typeof this.settings.onChange === 'function') {
            this.settings.onChange(this);
        }
    },

    onEnd: function() {
        if (typeof this.settings.onChange === 'function') {
            this.settings.onEnd(this);
        }
    },

    defaultSettings: {
        onStart: $.noop,
        onChange: $.noop,
        onEnd: $.noop
    }
});
