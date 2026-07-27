/**
 * Backwards-compatibility shims for deprecated legacy Craft CP jQuery plugins.
 *
 * These plugins used to live in the craftcms-legacy CP bundle. Core no longer
 * uses them; each shim below keeps third-party callers working while logging a
 * deprecation warning. Add future plugin shims to this file.
 *
 * Loaded CP-wide via CraftCms\Yii2Adapter\View\LegacyAssets\CpCompatAsset.
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
