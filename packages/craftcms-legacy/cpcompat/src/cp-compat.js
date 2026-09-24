/**
 * Backwards-compatibility shims for deprecated legacy Craft CP jQuery plugins.
 *
 * These plugins used to live in the craftcms-legacy CP bundle. Core no longer
 * uses them; each shim below keeps third-party callers working while logging a
 * deprecation warning. Add future plugin shims to this file.
 *
 * Compiled into the legacy asset package and loaded CP-wide via
 * CraftCms\Yii2Adapter\View\LegacyAssets\CpCompatAsset.
 */
(function () {
  'use strict';

  function init() {
    // Class stubs don't need jQuery, so register them before the guard below.
    registerRemovedClassStubs();

    var $ = window.jQuery;
    if (!$) {
      return;
    }

    registerInfoIcon($);
    registerInfoIconClass($);
    registerLightswitch($);
    registerLightSwitchClass($);
    registerPasswordInputClass($);
    registerColorInputClass($);
    registerSlidePickerClass($);
    registerTooltipClass($);
    autoUpgrade($);
  }

  /**
   * Deprecation stubs for legacy Craft CP classes that have been removed from
   * the core bundle. Core has no callers, but a third-party plugin might still
   * `new Craft.<Class>()` — without a stub that fails as an opaque
   * "undefined is not a constructor". Each stub warns once (matching the CP's
   * console.warn deprecation idiom) and is otherwise inert.
   */
  function registerRemovedClassStubs() {
    if (!window.Craft) {
      return;
    }

    defineRemovedClass(
      'Accordion',
      'Craft.Accordion has been removed. It was an unused legacy CP UI widget with no replacement.'
    );
    defineRemovedClass(
      'EnvVarGenerator',
      'Craft.EnvVarGenerator has been removed. It was an unused legacy input generator with no replacement.'
    );
    defineRemovedClass(
      'IconPicker',
      'Craft.IconPicker has been removed. Emit <craft-icon-picker> directly instead (or use Craft.ui.createIconPicker).'
    );
    defineRemovedClass(
      'SlideRuleInput',
      'Craft.SlideRuleInput has been removed. Emit <craft-slide-rule> directly instead.'
    );
    defineRemovedClass(
      'DeleteUserModal',
      'Craft.DeleteUserModal has been removed. It was deprecated in 5.10.0 and unused.'
    );
  }

  /**
   * Defines Craft.<name> as an inert Garnish.Base subclass that warns once when
   * instantiated or subclassed, preserving the extend()/new shape the removed
   * class had. Skips if something already defines the name.
   */
  function defineRemovedClass(name, message) {
    var Craft = window.Craft;
    if (Craft[name]) {
      return;
    }

    var warned = false;
    function warnOnce() {
      if (!warned) {
        warned = true;
        console.warn(message);
      }
    }

    if (window.Garnish && Garnish.Base && Garnish.Base.extend) {
      Craft[name] = Garnish.Base.extend({
        init: function () {
          warnOnce();
        },
      });
    } else {
      Craft[name] = function () {
        warnOnce();
      };
    }
  }

  /**
   * `$.fn.infoicon` — upgrades a legacy `.info` element to the
   * `<craft-info-icon>` web component. Core now emits the element directly, so
   * this only runs for third-party `.info` markup, and warns when it does.
   */
  function registerInfoIcon($) {
    if ($.fn.infoicon) {
      return;
    }

    var warned = false;

    $.fn.infoicon = function () {
      if (!warned) {
        warned = true;
        console.warn(
          '$(…).infoicon() is deprecated. Emit <craft-info-icon> directly instead.'
        );
      }
      return this.each(function () {
        upgradeInfoIconElement(this);
      });
    };
  }

  /**
   * Replaces a legacy `.info` element with a `<craft-info-icon>`, moving its
   * child nodes over (no innerHTML re-parse) and carrying the disabled state.
   * Returns the new element. Shared by `$.fn.infoicon` and Craft.InfoIcon.
   */
  function upgradeInfoIconElement(el) {
    var icon = document.createElement('craft-info-icon');
    if (el.classList && el.classList.contains('disabled')) {
      icon.setAttribute('disabled', '');
    }
    Array.prototype.slice.call(el.childNodes).forEach(function (node) {
      icon.appendChild(node);
    });
    el.replaceWith(icon);
    return icon;
  }

  /**
   * BC replacement for the removed `Craft.InfoIcon` class. The old class was
   * itself a deprecation shim: `new Craft.InfoIcon(el)` warned and converted
   * `el` into a `<craft-info-icon>`, exposing the result as `this.icon`. This
   * preserves that exact contract so programmatic callers keep working.
   */
  function registerInfoIconClass($) {
    var Craft = window.Craft;
    if (!Craft || Craft.InfoIcon) {
      return;
    }

    var warned = false;

    function InfoIcon(icon) {
      if (!(this instanceof InfoIcon)) {
        return new InfoIcon(icon);
      }
      if (!warned) {
        warned = true;
        console.warn(
          'Craft.InfoIcon is deprecated. Emit <craft-info-icon> directly instead.'
        );
      }
      this.icon = upgradeInfoIconElement(icon);
    }

    Craft.InfoIcon = InfoIcon;
  }

  /**
   * `$.fn.lightswitch` — upgrades legacy `.lightswitch` button markup to the
   * `<craft-switch>` web component. Core now emits the element directly (and
   * Craft.ui.createLightswitch builds it), so this only runs for third-party
   * `.lightswitch` markup, and warns when it does. The conversion reuses
   * Craft.ui.createLightswitch so the mapping stays in one place.
   */
  function registerLightswitch($) {
    if ($.fn.lightswitch) {
      return;
    }

    var warned = false;

    $.fn.lightswitch = function () {
      if (!warned) {
        warned = true;
        console.warn(
          '$(…).lightswitch() is deprecated. Emit <craft-switch> directly instead.'
        );
      }
      return this.each(function () {
        if (this.tagName === 'CRAFT-SWITCH') {
          return;
        }
        var $old = $(this);
        var $switch = upgradeLightswitchMarkup($, $old);
        if ($switch) {
          $old.replaceWith($switch);
        }
      });
    };
  }

  /**
   * Builds a `<craft-switch>` from legacy `.lightswitch` button markup via
   * Craft.ui.createLightswitch, keeping the legacy→component config mapping in
   * one place. Returns the new element (jQuery), or null if the component
   * factory isn't available. Does not touch the DOM — the caller swaps it in.
   * `overrides` lets a caller (e.g. Craft.LightSwitch) supply value /
   * indeterminateValue / name that aren't derivable from the markup.
   */
  function upgradeLightswitchMarkup($, $old, overrides) {
    if (
      !window.Craft ||
      !Craft.ui ||
      typeof Craft.ui.createLightswitch !== 'function'
    ) {
      return null;
    }
    overrides = overrides || {};
    var $input = $old.find('input[type="hidden"]').first();
    return Craft.ui.createLightswitch({
      on: $old.hasClass('on'),
      indeterminate: $old.hasClass('indeterminate'),
      small: $old.hasClass('small'),
      disabled: $old.hasClass('disabled') || $input.prop('disabled'),
      value: overrides.value || $old.attr('data-value') || '1',
      indeterminateValue:
        overrides.indeterminateValue ||
        $old.attr('data-indeterminate-value') ||
        '-',
      name: overrides.name || $input.attr('name'),
      id: $old.attr('id'),
      labelId: $old.attr('aria-labelledby'),
      toggle: $old.attr('data-target'),
      reverseToggle: $old.attr('data-reverse-target'),
    });
  }

  /**
   * BC replacement for the removed `Craft.LightSwitch` class. Warns once, then
   * delegates to the `<craft-switch>` element's imperative API (turnOn/turnOff/
   * turnIndeterminate/on), so `new Craft.LightSwitch(el, settings)` keeps
   * working. `el` may already be a `<craft-switch>`, or legacy `.lightswitch`
   * markup, which is upgraded in place. The legacy `.on` property was a boolean
   * (it shadowed any inherited event method), so the stub exposes it as a
   * getter rather than an event API.
   */
  function registerLightSwitchClass($) {
    var Craft = window.Craft;
    if (!Craft || Craft.LightSwitch) {
      return;
    }

    var warned = false;

    function LightSwitch(outerContainer, settings) {
      if (!(this instanceof LightSwitch)) {
        return new LightSwitch(outerContainer, settings);
      }
      if (!warned) {
        warned = true;
        console.warn(
          'Craft.LightSwitch is deprecated. Emit <craft-switch> directly, or build one with Craft.ui.createLightswitch().'
        );
      }

      this.settings = $.extend(
        {value: '1', indeterminateValue: '-', onChange: $.noop},
        settings || {}
      );

      var $el = $(outerContainer);
      if ($el[0] && $el[0].tagName !== 'CRAFT-SWITCH') {
        var $upgraded = upgradeLightswitchMarkup($, $el, this.settings);
        if ($upgraded) {
          $el.replaceWith($upgraded);
          $el = $upgraded;
        }
      }

      this.$outerContainer = $el;
      this.$switch = $el;
      this.$input = $el.find('input[type="hidden"]').first();

      var self = this;
      this._onChange = function () {
        self.settings.onChange(self.on);
      };
      $el.on('change', this._onChange);
    }

    LightSwitch.prototype = {
      constructor: LightSwitch,

      get on() {
        return this.$switch[0] ? !!this.$switch[0].on : false;
      },

      get indeterminate() {
        return this.$switch[0] ? !!this.$switch[0].indeterminate : false;
      },

      turnOn: function (muteEvent) {
        var el = this.$switch[0];
        if (el && typeof el.turnOn === 'function') {
          el.turnOn(muteEvent === true);
        }
      },

      turnOff: function (muteEvent) {
        var el = this.$switch[0];
        if (el && typeof el.turnOff === 'function') {
          el.turnOff(muteEvent === true);
        }
      },

      turnIndeterminate: function (muteEvent) {
        var el = this.$switch[0];
        if (el && typeof el.turnIndeterminate === 'function') {
          el.turnIndeterminate(muteEvent === true);
        }
      },

      toggle: function () {
        if (this.indeterminate || !this.on) {
          this.turnOn();
        } else {
          this.turnOff();
        }
      },

      destroy: function () {
        if (this._onChange) {
          this.$switch.off('change', this._onChange);
        }
      },
    };

    Craft.LightSwitch = LightSwitch;
  }

  /**
   * BC replacement for the removed `Craft.PasswordInput` class. Warns once, then
   * upgrades a legacy `<input type="password">` (and its `.passwordwrapper`) into
   * a `<craft-input-password>`, which provides the show/hide reveal toggle
   * natively — so `new Craft.PasswordInput(el, settings)` keeps producing a
   * working, toggleable password field. Exposes the legacy programmatic surface
   * (togglePassword etc.) as thin delegates to the element.
   */
  function registerPasswordInputClass($) {
    var Craft = window.Craft;
    if (!Craft || Craft.PasswordInput) {
      return;
    }

    var warned = false;

    function PasswordInput(passwordInput, settings) {
      if (!(this instanceof PasswordInput)) {
        return new PasswordInput(passwordInput, settings);
      }
      if (!warned) {
        warned = true;
        console.warn(
          'Craft.PasswordInput is deprecated. Emit <craft-input-password> directly instead.'
        );
      }

      this.settings = $.extend({onToggleInput: $.noop}, settings || {});

      var $input = $(passwordInput);
      this.$passwordInput = $input;

      // Already inside a craft-input-password (e.g. server-rendered)? Just adopt
      // it — nothing to upgrade.
      var $existing = $input.closest('craft-input-password');
      if ($existing.length) {
        this.$input = $existing;

        return;
      }

      var $el = $('<craft-input-password/>');
      var el = $input[0];
      if (el) {
        // Lion pushes these control props onto the slotted input on upgrade, so
        // mirror them onto the host (see InputPassword::hostAttributes()).
        if (el.name) {
          $el.attr('name', el.name);
        }
        if (el.placeholder) {
          $el.attr('placeholder', el.placeholder);
        }
        if (el.disabled) {
          $el.attr('disabled', '');
        }
        if (el.readOnly) {
          $el.attr('readonly', '');
        }
      }

      // Upgrade the legacy .passwordwrapper if present, else the bare input.
      var $wrapper = $input.parent('.passwordwrapper');
      var $target = $wrapper.length ? $wrapper : $input;
      $input.attr({type: 'password', slot: 'input'}).appendTo($el);
      $target.replaceWith($el);

      this.$input = $el;
    }

    PasswordInput.prototype = {
      constructor: PasswordInput,

      togglePassword: function () {
        var el = this.$input && this.$input[0];
        if (el && typeof el.reveal === 'function') {
          el.reveal();
        }
        this.settings.onToggleInput(this.$passwordInput);
      },

      // The web component owns the reveal state; these route through the same
      // toggle, but only when a change is actually needed (`type === 'text'`
      // means currently revealed).
      showPassword: function () {
        this._reveal(true);
      },
      hidePassword: function () {
        this._reveal(false);
      },
      _reveal: function (show) {
        var el = this.$input && this.$input[0];
        if (!el || typeof el.reveal !== 'function') {
          return;
        }
        if ((el.type === 'text') !== show) {
          el.reveal();
        }
        this.settings.onToggleInput(this.$passwordInput);
      },
      updateToggleLabel: function () {},
      destroy: function () {},
    };

    Craft.PasswordInput = PasswordInput;
  }

  /**
   * BC replacement for the removed `Craft.ColorInput` class. Warns once, then
   * upgrades a legacy `.color-container` (its `.color-input` text field, swatch,
   * and `#` indicator) into a `<craft-input-color>`, which provides the picker,
   * swatch, and hex handling natively — so `new Craft.ColorInput(container,
   * {presets})` keeps producing a working color input.
   */
  function registerColorInputClass($) {
    var Craft = window.Craft;
    if (!Craft || Craft.ColorInput) {
      return;
    }

    var warned = false;

    function ColorInput(container, settings) {
      if (!(this instanceof ColorInput)) {
        return new ColorInput(container, settings);
      }
      if (!warned) {
        warned = true;
        console.warn(
          'Craft.ColorInput is deprecated. Emit <craft-input-color> directly instead.'
        );
      }

      this.settings = $.extend({presets: []}, settings || {});

      var $container = $(container);
      this.$container = $container;
      var $input = $container.find('.color-input').first();
      this.$input = $input;

      // Already upgraded (server-rendered craft-input-color)? Nothing to do.
      if ($input.closest('craft-input-color').length) {
        return;
      }

      var $el = $('<craft-input-color/>');
      var el = $input[0];
      if (el) {
        // Lion pushes these control props onto the slotted input on upgrade, so
        // mirror them onto the host (see InputColor::hostAttributes()). The
        // legacy .color-input value is already bare hex (# stripped).
        if (el.name) {
          $el.attr('name', el.name);
        }
        if (el.disabled) {
          $el.attr('disabled', '');
        }
      }
      if (this.settings.presets && this.settings.presets.length) {
        $el.attr('presets', JSON.stringify(this.settings.presets));
      }

      $input.attr('slot', 'input').appendTo($el);
      $container.replaceWith($el);

      this.$el = $el;
    }

    Craft.ColorInput = ColorInput;
  }

  /**
   * BC replacement for `Craft.SlidePicker`. Keeps the legacy constructor shape
   * (`new Craft.SlidePicker(value, settings)`) while rendering a
   * `<craft-slide-picker>` element under `this.$container`.
   */
  function registerSlidePickerClass($) {
    var Craft = window.Craft;
    if (!Craft || Craft.SlidePicker) {
      return;
    }

    var warned = false;

    function warnOnce() {
      if (!warned) {
        warned = true;
        console.warn(
          'Craft.SlidePicker is deprecated. Emit <craft-slide-picker> directly instead (or use Craft.ui.createSlidePicker).'
        );
      }
    }

    function SlidePicker(value, settings) {
      if (!(this instanceof SlidePicker)) {
        return new SlidePicker(value, settings);
      }

      warnOnce();

      this.settings = $.extend(
        {
          min: 0,
          max: 100,
          step: 10,
          valueLabel: null,
          onChange: $.noop,
          readOnly: false,
          label: null,
          describedBy: null,
        },
        settings || {}
      );

      this.value = null;
      this.min = null;
      this.max = null;
      this.totalSteps = null;
      this.$buttons = $();
      this.$container = $('<craft-slide-picker/>');

      this.refresh();
      this.setValue(value, false);

      var self = this;
      this.$container.on('change', function () {
        self.setValue(self.$container[0].value);
      });
    }

    SlidePicker.prototype.refresh = function () {
      this.min = this._min();
      this.max = this._max();
      this.totalSteps = (this.max - this.min) / this.settings.step;

      if (!Number.isInteger(this.totalSteps)) {
        throw 'Invalid SlidePicker config';
      }

      var el = this.$container[0];
      el.min = this.min;
      el.max = this.max;
      el.step = this.settings.step;
      el.valueLabel =
        this.settings.valueLabel ||
        function (value) {
          return String(value);
        };
      el.label = this.settings.label || Craft.t('app', 'Number of columns');

      if (this.settings.describedBy) {
        el.setAttribute('described-by', this.settings.describedBy);
      } else {
        el.removeAttribute('described-by');
      }

      el.readonly = !!this.settings.readOnly;

      this.$buttons = this.$container.find('.slide-picker__segment');

      if (this.value !== null) {
        var value = this.value;
        this.value = null;
        this.setValue(value, false);
      }
    };

    SlidePicker.prototype.setValue = function (value, triggerEvent) {
      value = Math.max(Math.min(value, this.max), this.min);
      value = Math.round(value / this.settings.step) * this.settings.step;

      if (this.value === (this.value = value)) {
        return;
      }

      this.$container[0].value = this.value;
      this.$buttons = this.$container.find('.slide-picker__segment');

      if (triggerEvent !== false) {
        this.settings.onChange(value);
      }
    };

    SlidePicker.prototype._min = function () {
      if (typeof this.settings.min === 'function') {
        return this.settings.min();
      }
      return this.settings.min;
    };

    SlidePicker.prototype._max = function () {
      if (typeof this.settings.max === 'function') {
        return this.settings.max();
      }
      return this.settings.max;
    };

    Craft.SlidePicker = SlidePicker;
  }

  /**
   * BC replacement for the removed `Craft.Tooltip` class. Warns once, then
   * delegates to a `<craft-tooltip>` web component pointed at the trigger (by
   * id), so `new Craft.Tooltip(trigger, message)` keeps showing the message on
   * hover/focus. Exposes the legacy surface (message/$trigger/show/hide/toggle).
   */
  function registerTooltipClass($) {
    var Craft = window.Craft;
    if (!Craft || Craft.Tooltip) {
      return;
    }

    var warned = false;

    function Tooltip(trigger, message) {
      if (!(this instanceof Tooltip)) {
        return new Tooltip(trigger, message);
      }
      if (!warned) {
        warned = true;
        console.warn(
          'Craft.Tooltip is deprecated. Emit <craft-tooltip for="…"> directly instead.'
        );
      }
      this.$el = null;
      this.setTrigger(trigger);
      this.message = message;
    }

    Tooltip.prototype = {
      constructor: Tooltip,

      setTrigger: function (trigger) {
        var $trigger = $(trigger);
        this._$trigger = $trigger;
        var el = $trigger[0];
        if (!el) {
          return;
        }
        if (!el.id) {
          el.id = 'tooltip-trigger-' + Math.floor(Math.random() * 1000000);
        }
        if (!this.$el) {
          this.$el = $('<craft-tooltip/>')
            .attr('for', el.id)
            .appendTo(document.body);
        } else {
          this.$el.attr('for', el.id);
        }
      },

      get $trigger() {
        return this._$trigger;
      },
      set $trigger(value) {
        this.setTrigger(value);
      },

      get message() {
        return this._message;
      },
      set message(value) {
        this._message = value;
        if (this.$el) {
          this.$el.text(value == null ? '' : value);
        }
      },

      get showing() {
        return !!(this.$el && this.$el[0] && this.$el[0].opened);
      },

      show: function () {
        if (this.$el && this.$el[0] && typeof this.$el[0].show === 'function') {
          this.$el[0].show();
        }
      },
      hide: function () {
        if (this.$el && this.$el[0] && typeof this.$el[0].hide === 'function') {
          this.$el[0].hide();
        }
      },
      toggle: function () {
        if (this.showing) {
          this.hide();
        } else {
          this.show();
        }
      },
      destroy: function () {
        if (this.$el) {
          this.$el.remove();
          this.$el = null;
        }
      },
    };

    Craft.Tooltip = Tooltip;
  }

  /**
   * Preserves the legacy auto-upgrade of third-party `.info`/`.lightswitch`
   * markup: sweep on load and whenever Craft.initUiElements runs on injected
   * content. The deprecation warnings fire only when such markup exists.
   */
  function autoUpgrade($) {
    function sweep($container) {
      try {
        $('.info', $container).infoicon();
      } catch (e) {
        // no-op
      }
      try {
        $('.lightswitch', $container).lightswitch();
      } catch (e) {
        // no-op
      }
    }

    if (window.Craft && typeof Craft.initUiElements === 'function') {
      var initUiElements = Craft.initUiElements;
      Craft.initUiElements = function ($container) {
        var result = initUiElements.apply(this, arguments);
        sweep($container || document);
        return result;
      };
    }

    // Initial pass for markup already present in the DOM.
    sweep(document);
  }

  // Run after the DOM is ready so jQuery and Craft (both loaded via blocking
  // scripts) are guaranteed to be present, regardless of this file's position.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
