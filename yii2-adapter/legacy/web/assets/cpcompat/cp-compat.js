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
    registerLightswitch($);
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

    function upgrade() {
      var icon = document.createElement('craft-info-icon');
      if (this.classList.contains('disabled')) {
        icon.setAttribute('disabled', '');
      }
      Array.prototype.slice.call(this.childNodes).forEach(function (node) {
        icon.appendChild(node);
      });
      this.replaceWith(icon);
    }

    $.fn.infoicon = function () {
      if (!warned) {
        warned = true;
        console.warn(
          '$(…).infoicon() is deprecated. Emit <craft-info-icon> directly instead.'
        );
      }
      return this.each(upgrade);
    };
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
        if (
          this.tagName === 'CRAFT-SWITCH' ||
          !window.Craft ||
          !Craft.ui ||
          typeof Craft.ui.createLightswitch !== 'function'
        ) {
          return;
        }
        var $old = $(this);
        var $input = $old.find('input[type="hidden"]').first();
        var $switch = Craft.ui.createLightswitch({
          on: $old.hasClass('on'),
          indeterminate: $old.hasClass('indeterminate'),
          small: $old.hasClass('small'),
          disabled: $old.hasClass('disabled') || $input.prop('disabled'),
          value: $old.attr('data-value') || '1',
          indeterminateValue: $old.attr('data-indeterminate-value') || '-',
          name: $input.attr('name'),
          id: $old.attr('id'),
          labelId: $old.attr('aria-labelledby'),
          toggle: $old.attr('data-target'),
          reverseToggle: $old.attr('data-reverse-target'),
        });
        $old.replaceWith($switch);
      });
    };
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
