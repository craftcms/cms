/** global: Craft */
/** global: Garnish */
import postcssValueParser from 'tailwindcss/src/value-parser';

/**
 * FieldToggle
 */
Craft.FieldToggle = Garnish.Base.extend({
  $toggle: null,
  targetPrefix: null,
  targetSelector: null,
  reverseTargetSelector: null,

  _$target: null,
  _$reverseTarget: null,
  type: null,

  init: function (toggle) {
    this.$toggle = $(toggle);

    // Is this already a field toggle?
    if (this.$toggle.data('fieldtoggle')) {
      console.warn('Double-instantiating a field toggle on an element');
      this.$toggle.data('fieldtoggle').destroy();
    }

    this.$toggle.data('fieldtoggle', this);

    this.type = this.getType();

    if (
      this.type === 'select' ||
      this.type === 'fieldset' ||
      Garnish.hasAttr(this.$toggle, 'data-target-prefix')
    ) {
      this.targetPrefix = this.$toggle.attr('data-target-prefix') || '';
    } else {
      this.targetSelector = this.normalizeTargetSelector(
        this.$toggle.data('target')
      );
      this.reverseTargetSelector = this.normalizeTargetSelector(
        this.$toggle.data('reverse-target')
      );
    }

    this.findTargets();

    switch (this.type) {
      case 'button':
        if (this._isButtonToggle()) {
          if (!this._$target.attr('id')) {
            this._$target.attr(
              'id',
              `toggle-target-${Math.floor(Math.random() * 1000000)}`
            );
          }
          this.$toggle.attr('aria-controls', this._$target.attr('id'));
          this._updateButtonExpanded();
        }
        this.addListener(this.$toggle, 'activate', 'onToggleChange');
        break;
      case 'fieldset':
        this.addListener(
          this.$toggle.find('input'),
          'change',
          'onToggleChange'
        );
        break;
      default:
        this.addListener(this.$toggle, 'change', 'onToggleChange');
        this.onToggleChange();
    }
  },

  normalizeTargetSelector: function (selector) {
    if (selector && !selector.match(/^[#\.]/)) {
      selector = '#' + selector;
    }

    return selector;
  },

  getType: function () {
    let nodeName = this._toggleNodeName();
    if (
      (nodeName === 'INPUT' && this.$toggle.attr('type') === 'checkbox') ||
      this.$toggle.attr('role') === 'checkbox' ||
      this.$toggle.attr('role') === 'switch'
    ) {
      return 'checkbox';
    }

    switch (nodeName) {
      case 'SELECT':
        if (Garnish.hasAttr(this.$toggle, 'data-boolean-menu')) {
          return 'booleanMenu';
        }
        return 'select';
      case 'BUTTON':
      case 'A':
        return 'button';
      default:
        return 'fieldset';
    }
  },

  findTargets: function () {
    if (this.targetPrefix !== null) {
      this._$target = $(
        this.normalizeTargetSelector(
          this.targetPrefix + (this.getToggleVal() || '')
        )
      );
    } else {
      if (this.targetSelector) {
        this._$target = $(this.targetSelector);
      }

      if (this.reverseTargetSelector) {
        this._$reverseTarget = $(this.reverseTargetSelector);
      }
    }
  },

  getToggleVal: function () {
    if (this.type === 'checkbox' && this.targetPrefix === null) {
      if (typeof this.$toggle.prop('checked') !== 'undefined') {
        return this.$toggle.prop('checked');
      }
      return this.$toggle.attr('aria-checked') === 'true';
    }

    if (this.type === 'booleanMenu') {
      const boolean = this.$toggle.data('boolean');
      if (typeof boolean !== 'undefined') {
        return boolean;
      }
      const val = this.$toggle.val();
      return val && val !== '0';
    }

    if (this.type === 'fieldset') {
      return this.normalizeToggleVal(
        this.$toggle.find('input:checked:first').val()
      );
    }

    return this.normalizeToggleVal(this.$toggle.val());
  },

  normalizeToggleVal: function (val) {
    if (!val) {
      return null;
    }
    return val.replace(/[^\w]+/g, '-');
  },

  onToggleChange: async function (force = false) {
    // is this a selectize input and does it look like it was just opened?
    const selectize = this.$toggle.data('selectize');
    if (selectize && this.$toggle.val() === '') {
      await Craft.sleep(1);
      if (selectize.isOpen) {
        return;
      }
    }

    if (this.type === 'select' || this.type === 'fieldset') {
      this.hideTarget(this._$target);
      this.findTargets();
      this.showTarget(this._$target);
    } else {
      this.findTargets();

      if (this.type === 'button') {
        this.onToggleChange._show = this._buttonIsCollapsed();
      } else if (this.type === 'checkbox' && this.targetPrefix !== null) {
        this.onToggleChange._show = this.$toggle.prop('checked');
      } else {
        this.onToggleChange._show = !!this.getToggleVal();
      }

      if (this.onToggleChange._show) {
        this.showTarget(this._$target);
        this.hideTarget(this._$reverseTarget);
      } else {
        this.hideTarget(this._$target);
        this.showTarget(this._$reverseTarget);
      }

      delete this.onToggleChange._show;
    }

    this.trigger('toggleChange');
  },

  showTarget: function ($target) {
    if ($target && $target.length) {
      this.showTarget._currentHeight = $target.height();

      $target.removeClass('hidden');

      if (this.type !== 'select' && this.type !== 'fieldset') {
        if (this.type === 'button') {
          this.$toggle.removeClass('collapsed');
          this.$toggle.addClass('expanded');
          if (this._isButtonToggle()) {
            this._updateButtonExpanded();
          }
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
        if (this.type === 'button') {
          this.$toggle.removeClass('expanded');
          this.$toggle.addClass('collapsed');
          if (this._isButtonToggle()) {
            this._updateButtonExpanded();
          }
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

  _toggleNodeName: function () {
    return this.$toggle.prop('nodeName');
  },

  _isButtonToggle: function () {
    return this._toggleNodeName() === 'BUTTON';
  },

  _buttonIsCollapsed: function () {
    return (
      this.$toggle.hasClass('collapsed') || !this.$toggle.hasClass('expanded')
    );
  },

  _updateButtonExpanded() {
    this.$toggle.attr(
      'aria-expanded',
      this._buttonIsCollapsed() ? 'false' : 'true'
    );
  },

  destroy: function () {
    this.$toggle.removeData('fieldtoggle');
    this.base();
  },
});
