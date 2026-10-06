<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\View\Enums\Position;

/**
 * Compatibility shim for legacy ready-JS on bridged screens.
 *
 * A bridged screen's markup is rendered by the server but put into the document
 * by Vue, after the page has parsed. Legacy JS registered through Craft's
 * `{% js %}` tag runs on `DOMContentLoaded`, which by then is either too early
 * (the markup isn't mounted) or already past. Either way a plugin's boot code
 * looks for elements that aren't there — `new Vue({el: '#…-container'})` being
 * the canonical example.
 *
 * This defines `Craft.whenReady()`, which {@see HtmlStack::readyJs()} hands its
 * callbacks to when it exists. Callbacks queue until {@see self::FLUSH_METHOD}
 * is called, which the fragment screen does once every fragment is in the DOM.
 *
 * Scope and removal:
 *
 * - Only the legacy-screen bridge registers this, so ordinary control panel
 *   pages never see it and keep the stock `DOMContentLoaded` behavior.
 * - To retire the shim entirely: delete this class, its two call sites in the
 *   bridge, the `whenReady` branch in {@see HtmlStack::readyJs()}, and the
 *   flush in the fragment screen. Nothing else refers to it.
 * - To retire it for one screen while its author modernizes, skip the
 *   {@see self::register()} call for that screen; its ready-JS then runs on
 *   `DOMContentLoaded` exactly as it does today.
 *
 * @internal
 *
 * @deprecated Exists only while legacy screens render inside the Inertia shell.
 */
final class LegacyReadyShim
{
    /** The client-side method that releases queued callbacks. */
    public const FLUSH_METHOD = 'Craft.flushReady';

    /**
     * Registers the shim for the current response.
     *
     * Emitted at {@see Position::Head} so it is defined before any ready-JS the
     * screen registers, which lands at the end of the body.
     */
    public static function register(): void
    {
        HtmlStack::js(self::js(), Position::Head);
    }

    private static function js(): string
    {
        return <<<'JS'
window.Craft = window.Craft || {};

(() => {
  if (typeof window.Craft.whenReady === 'function') {
    return;
  }

  let pending = [];
  let released = false;

  /**
   * Queues a legacy ready callback until the screen's markup is in the DOM.
   * Callbacks registered after the release run immediately, so late-arriving
   * fragments behave the same as early ones.
   */
  window.Craft.whenReady = (callback) => {
    if (released) {
      callback();

      return;
    }

    pending.push(callback);
  };

  window.Craft.flushReady = () => {
    released = true;

    // Callbacks may register further callbacks; draining rather than
    // iterating keeps those in order instead of dropping them.
    while (pending.length) {
      pending.shift()();
    }

    pending = [];
  };
})();
JS;
    }
}
