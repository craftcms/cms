/** global: Craft */
/** global: Garnish */
/**
 * Accordion
 */
Craft.Accordion = Garnish.Base.extend({
  $trigger: null,
  targetPrefix: null,
  targetSelector: null,

  _$target: null,

  init: function (trigger) {
    this.$trigger = $(trigger);

    // Is this already a field toggle?
    if (this.$trigger.data('accordion')) {
      console.warn('Double-instantiating an accordion trigger on an element');
      this.$trigger.data('accordion').destroy();
    }

    this.$trigger.data('accordion', this);
    this.targetSelector = this.$trigger.attr('aria-controls')
      ? `#${this.$trigger.attr('aria-controls')}`
      : null;

    if (this.targetSelector) {
      this._$target = $(this.targetSelector);
    }

    this.addListener(this.$trigger, 'click', 'onTriggerClick');
    this.addListener(this.$trigger, 'keypress', (event) => {
      const key = event.keyCode;

      if (key === Garnish.SPACE_KEY || key === Garnish.RETURN_KEY) {
        event.preventDefault();
        this.onTriggerClick();
      }
    });
  },

  onTriggerClick: function () {
    const isOpen = this.$trigger.attr('aria-expanded') === 'true';

    if (isOpen) {
      this.hideTarget(this._$target);
    } else {
      this.showTarget(this._$target);
    }
  },

  showTarget: function ($target) {
    if ($target && $target.length) {
      this.showTarget._currentHeight = $target.height();

      $target.removeClass('hidden');

      this.$trigger
        .removeClass('collapsed')
        .addClass('expanded')
        .attr('aria-expanded', 'true');

      for (let i = 0; i < $target.length; i++) {
        (($t) => {
          if ($t.prop('nodeName') !== 'SPAN') {
            $t.height('auto');
            this.showTarget._targetHeight = $t.height();
            $t.css({
              height: this.showTarget._currentHeight,
              overflow: 'hidden',
            });

            $t.velocity('stop');

            $t.velocity(
              {height: this.showTarget._targetHeight},
              'fast',
              function () {
                $t.css({
                  height: '',
                  overflow: '',
                });
              }
            );
          }
        })($target.eq(i));
      }

      delete this.showTarget._targetHeight;
      delete this.showTarget._currentHeight;

      // Trigger a resize event in case there are any grids in the target that need to initialize
      Garnish.$win.trigger('resize');
    }
  },

  hideTarget: function ($target) {
    if ($target && $target.length) {
      this.$trigger
        .removeClass('expanded')
        .addClass('collapsed')
        .attr('aria-expanded', 'false');

      for (let i = 0; i < $target.length; i++) {
        (($t) => {
          if ($t.hasClass('hidden')) {
            return;
          }
          if ($t.prop('nodeName') === 'SPAN') {
            $t.addClass('hidden');
          } else {
            $t.css('overflow', 'hidden');
            $t.velocity('stop');
            $t.velocity({height: 0}, 'fast', function () {
              $t.addClass('hidden');
            });
          }
        })($target.eq(i));
      }
    }
  },

  destroy: function () {
    this.$trigger.removeData('accordion');
    this.base();
  },
});
