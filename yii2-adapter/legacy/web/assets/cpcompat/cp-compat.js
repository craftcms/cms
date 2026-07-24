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
    var $ = window.jQuery;
    if (!$) {
      return;
    }

    registerInfoIcon($);
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

    // Preserve BC auto-upgrade of legacy third-party `.info` markup injected
    // after load (fires the warning only when such markup exists).
    if (window.Craft && typeof Craft.initUiElements === 'function') {
      var initUiElements = Craft.initUiElements;
      Craft.initUiElements = function ($container) {
        var result = initUiElements.apply(this, arguments);
        try {
          $('.info', $container || document).infoicon();
        } catch (e) {
          // no-op
        }
        return result;
      };
    }

    // Initial pass for legacy `.info` already present in the DOM.
    try {
      $('.info').infoicon();
    } catch (e) {
      // no-op
    }
  }

  // Run after the DOM is ready so jQuery and Craft (both loaded via blocking
  // scripts) are guaranteed to be present, regardless of this file's position.
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
