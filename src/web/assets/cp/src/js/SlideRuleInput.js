/** global: Garnish */

Craft.SlideRuleInput = Garnish.Base.extend({
  $container: null,
  $options: null,
  $selectedOption: null,
  $input: null,
  value: null,
  dragging: false,
  sensitivity: 3,
  rotateIntent: false,

  startPositionX: null,

  init: function (id, settings) {
    this.setSettings(settings, Craft.SlideRuleInput.defaultSettings);

    this.value = 0;
    this.graduationsMin = -70;
    this.graduationsMax = 70;
    this.slideMin = -45;
    this.slideMax = 45;

    this.$container = $('#' + id);
    this.$overlay = $('<div class="overlay"></div>').appendTo(this.$container);
    this.$cursor = $('<div class="cursor"></div>').appendTo(this.$container);
    this.$graduations = $('<div class="graduations"></div>').appendTo(
      this.$container
    );
    this.$graduationsUl = $('<ul></ul>')
      .attr({
        'aria-hidden': 'true',
      })
      .appendTo(this.$graduations);

    this.$container.attr({
      role: 'slider',
      tabindex: '0',
      'aria-valuemin': this.slideMin,
      'aria-valuemax': this.slideMax,
      'aria-valuenow': '0',
      'aria-valuetext': Craft.t(
        'app',
        '{num, number} {num, plural, =1{degree} other{degrees}}',
        {
          num: 0,
        }
      ),
    });

    for (var i = this.graduationsMin; i <= this.graduationsMax; i++) {
      var $li = $(
        '<li class="graduation" data-graduation="' +
          i +
          '"><div class="label">' +
          i +
          '</div></li>'
      ).appendTo(this.$graduationsUl);

      if (i % 5 === 0) {
        $li.addClass('main-graduation');
      }

      if (i === 0) {
        $li.addClass('selected');
      }
    }

    this.$options = this.$container.find('.graduation');

    this.addListener(this.$container, 'resize', this._handleResize.bind(this));
    this.addListener(
      this.$container,
      'tapstart',
      this._handleTapStart.bind(this)
    );
    this.addListener(Garnish.$bod, 'tapmove', this._handleTapMove.bind(this));
    this.addListener(Garnish.$bod, 'tapend', this._handleTapEnd.bind(this));
    this.addListener(
      this.$container,
      'keydown',
      this._handleKeypress.bind(this)
    );

    // Set to zero

    // this.setValue(0);

    setTimeout(() => {
      // (n -1) options because the border is placed on the left of the 10px box
      this.graduationsCalculatedWidth = (this.$options.length - 1) * 10;
      this.$graduationsUl.css(
        'left',
        -this.graduationsCalculatedWidth / 2 + this.$container.width() / 2
      );
    }, 50);
  },

  _handleResize: function () {
    var left = this.valueToPosition(this.value);
    this.$graduationsUl.css('left', left);
  },

  _handleKeypress: function (event) {
    const current = parseInt(this.$container.attr('aria-valuenow'), 10);

    switch (event.keyCode) {
      case Garnish.UP_KEY:
      case Garnish.RIGHT_KEY:
        this.setValue(current + 1);
        break;
      case Garnish.DOWN_KEY:
      case Garnish.LEFT_KEY:
        this.setValue(current - 1);
        break;
      case Garnish.PAGE_UP_KEY:
        this.setValue(current + 10);
        break;
      case Garnish.PAGE_DOWN_KEY:
        this.setValue(current - 10);
        break;
      case Garnish.HOME_KEY:
        this.setValue(this.slideMin);
        break;
      case Garnish.END_KEY:
        this.setValue(this.slideMax);
        break;
    }

    this.onChange();
  },

  _handleTapStart: function (ev, touch) {
    ev.preventDefault();

    // Only allow rotation if the user taps on anything within the graduations container
    this.rotateIntent = $(ev.target).is('.graduations *');

    if (!this.rotateIntent) return;

    this.startPositionX = touch.position.x;
    this.startLeft = this.$graduationsUl.position().left;

    this.onStart();
  },

  _handleTapMove: function (ev, touch) {
    if (!this.rotateIntent) return;

    if (Math.abs(touch.position.x - this.startPositionX) > this.sensitivity) {
      this.dragging = true;
      this.$container.addClass('dragging');
      ev.preventDefault();
      this._setValueFromTouch(touch);
      this.onChange();
    }
  },

  _setValueFromTouch: function (touch) {
    let referencePosition = this.dragging
      ? this.startPositionX
      : this.$cursor.offset().left + this.$cursor.outerWidth() / 2;
    let delta;

    if (this.dragging) {
      delta = referencePosition - touch.position.x;
    } else {
      delta = touch.position.x - referencePosition;
    }

    const position = this.startLeft - delta;
    const value = this.positionToValue(position);
    this.setValue(value);
  },

  setValue: function (value) {
    var left = this.valueToPosition(value);
    if (value < this.slideMin) {
      value = this.slideMin;
      left = this.valueToPosition(value);
    } else if (value > this.slideMax) {
      value = this.slideMax;
      left = this.valueToPosition(value);
    }

    this.$graduationsUl.css('left', left);

    if (value >= this.slideMin && value <= this.slideMax) {
      this.$options.removeClass('selected');

      $.each(this.$options, function (key, option) {
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

    this.$container.attr({
      'aria-valuenow': value,
      'aria-valuetext': Craft.t(
        'app',
        '{num, number} {num, plural, =1{degree} other{degrees}}',
        {num: parseInt(value, 10)}
      ),
    });
    this.value = value;
  },

  _handleTapEnd: function (ev, touch) {
    if (!this.rotateIntent) return;

    if (this.dragging) {
      ev.preventDefault();
      this.dragging = false;
      this.$container.removeClass('dragging');
    } else {
      this._setValueFromTouch(touch);
      this.onChange();
    }

    this.onEnd();
    this.startPositionX = null;
    this.rotateIntent = false;
  },

  positionToValue: function (position) {
    var scaleMin = this.graduationsMin * -1;
    var scaleMax = (this.graduationsMin - this.graduationsMax) * -1;

    return (
      ((this.$graduations.width() / 2 + position * -1) /
        this.graduationsCalculatedWidth) *
        scaleMax -
      scaleMin
    );
  },

  valueToPosition: function (value) {
    var scaleMin = this.graduationsMin * -1;
    var scaleMax = (this.graduationsMin - this.graduationsMax) * -1;

    return -(
      ((value + scaleMin) * this.graduationsCalculatedWidth) / scaleMax -
      this.$graduations.width() / 2
    );
  },

  onStart: function () {
    if (typeof this.settings.onChange === 'function') {
      this.settings.onStart(this);
    }
  },

  onChange: function () {
    if (typeof this.settings.onChange === 'function') {
      this.settings.onChange(this);
    }
  },

  onEnd: function () {
    if (typeof this.settings.onChange === 'function') {
      this.settings.onEnd(this);
    }
  },

  defaultSettings: {
    onStart: $.noop,
    onChange: $.noop,
    onEnd: $.noop,
  },
});
