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

    this.addListener(this.$trigger, 'click', 'onTriggerClick');
  },

  normalizeTargetSelector: function (selector) {
    if (selector && !selector.match(/^[#\.]/)) {
      selector = '#' + selector;
    }

    return selector;
  },

  onTriggerClick: function () {
    console.log(this.$trigger.attr('aria-expanded'));

    const isOpen = this.$trigger.attr('aria-expanded') === 'true';

    if (isOpen) {
      console.log('hide');
    } else {
      console.log('show');
    }
  },

  showTarget: function ($target) {
    if ($target && $target.length) {
      this.showTarget._currentHeight = $target.height();

      $target.removeClass('hidden');

      if (this.type !== 'select' && this.type !== 'fieldset') {
        if (this.type === 'link') {
          this.$toggle.removeClass('collapsed');
          this.$toggle.addClass('expanded');
        }

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
      }

      delete this.showTarget._currentHeight;

      // Trigger a resize event in case there are any grids in the target that need to initialize
      Garnish.$win.trigger('resize');
    }
  },

  hideTarget: function ($target) {
    if ($target && $target.length) {
      if (this.type === 'select' || this.type === 'fieldset') {
        $target.addClass('hidden');
      } else {
        if (this.type === 'link') {
          this.$toggle.removeClass('expanded');
          this.$toggle.addClass('collapsed');
        }

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
    }
  },

  destroy: function () {
    this.$trigger.removeData('accordion');
    this.base();
  },
});
