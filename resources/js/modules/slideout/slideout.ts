/**
 * Slideout — ported from the legacy jQuery `Craft.Slideout`
 * (`packages/craftcms-legacy/cp/src/js/Slideout.js`), onto the modern,
 * `@craftcms/garnish` `Base`.
 *
 * Unlike most other module ports, `Craft.Slideout` still has a *real* legacy
 * subclass: `Craft.CpScreenSlideout` (`packages/craftcms-legacy/cp/src/js/CpScreenSlideout.js`)
 * subclasses it via the legacy Dean-Edwards `Garnish.Base.extend({...}, {...})`
 * API and calls `this.base(contents, settings)` from its own `init`, expecting
 * that to run the ancestor's `init`. `Craft.ElementEditorSlideout` subclasses
 * `CpScreenSlideout` the same way. To keep that working:
 *
 * - {@link init} is a real public method, invoked from the constructor only
 *   for the leaf class (the `new.target === Slideout` guard below) — the same
 *   construction contract as every other port. A legacy subclass built via
 *   `compatify(Slideout).extend({init: function (…) { this.base(…); }})`
 *   reaches this method through `this.base()`.
 * - `index.ts` assigns `window.Craft.Slideout = compatify(Slideout)` — **not**
 *   the plain class — precisely so that legacy `.extend()` chain keeps
 *   working. See `compatify`/`makeSubclass` in
 *   `packages/craftcms-garnish/src/compat.ts`: each `.extend()` produces a
 *   real subclass (native prototype chain), and `init` methods that reference
 *   `base` get wrapped so `this.base` resolves to the ancestor implementation
 *   found on that prototype chain — which, for the leaf-most `.extend()`
 *   level, is this class's own `init`.
 *
 * What deliberately stays jQuery: `$outerContainer`, `$container`, `$shade`,
 * `$triggerElement`, and `$liveRegion` remain jQuery collections (not native
 * elements) because the subclass and external code depend on that shape —
 * `ElementEditor.js`'s `this.$container.data('slideout')`, `CP.js`'s
 * `$modal.find('.slideout').data('slideout')`, and `CpScreenSlideout`'s own
 * jQuery-heavy internals. `declare const $` / `declare const Craft` are page
 * globals, same as every other port.
 */

import {
  Base,
  Garnish,
  initGarnish,
  ESC_KEY,
  bod,
  addModalAttributes,
  hideModalBackgroundLayers,
  resetModalBackgroundLayerVisibility,
  prefersReducedMotion,
  type GarnishBaseSettings,
} from '@craftcms/garnish';
import {containerSlideouts} from './support';

declare const Craft: any;
declare const $: any;

// Populate `Garnish.uiLayerManager` (idempotent) so it's available the first
// time a slideout opens, regardless of whether anything else has imported
// `@craftcms/garnish/compat` (which also calls this) yet.
initGarnish();

/**
 * The UI-layer manager slideouts should coordinate with. The legacy garnish
 * bundle instantiates its OWN `Garnish.UiLayerManager` singleton, and every
 * legacy layer consumer (modals, menus, HUDs) registers with it — if slideouts
 * used the modern singleton on those pages, Escape handling and layer stacking
 * would run in two disconnected managers (e.g. one Escape press closing both a
 * menu and the slideout behind it). Prefer the page's legacy manager when
 * present; fall back to the modern one for legacy-free surfaces.
 */
function uiLayerManager(): any {
  return (window as any).Garnish?.uiLayerManager ?? Garnish.uiLayerManager!;
}

/**
 * Same split-brain concern as {@link uiLayerManager}: the modern
 * `hideModalBackgroundLayers`/`resetModalBackgroundLayerVisibility` utils keep
 * their own hidden-layer bookkeeping, separate from the legacy bundle's. Use
 * the page's legacy implementations when present so a slideout's hide/reset
 * pairs with the modals/HUDs it stacks against.
 */
function hideBackgroundLayers(): void {
  const legacy = (window as any).Garnish?.hideModalBackgroundLayers;
  (legacy ?? hideModalBackgroundLayers)();
}

function resetBackgroundLayerVisibility(): void {
  const legacy = (window as any).Garnish?.resetModalBackgroundLayerVisibility;
  (legacy ?? resetModalBackgroundLayerVisibility)();
}

/**
 * The legacy `Craft.Slideout` prototype default was a single jQuery element
 * literal (`$liveRegion: $('<span .../>')`), evaluated once when
 * `Garnish.Base.extend()` ran — every instance shared the SAME `<span>` node,
 * moved (not cloned) into whichever container most recently called
 * {@link Slideout.init}. That's faithfully reproduced here as a module-scoped
 * singleton rather than a fresh per-instance element, since screen readers
 * only need one live region announcing at a time and changing that would be a
 * behavior change, not just a port.
 */
const $sharedLiveRegion = $(
  '<span class="visually-hidden" role="status"></span>'
);

/**
 * Settings accepted by {@link Slideout}. Pass a `Partial<SlideoutSettings>` to
 * the constructor; unset keys fall back to {@link Slideout.defaults}.
 */
export interface SlideoutSettings extends GarnishBaseSettings {
  /** Tag name for the generated container element. Default `'div'`. */
  containerElement: string;
  /** Attributes applied to the container element via jQuery's `$(tag, attrs)`. */
  containerAttributes: Record<string, unknown>;
  /** Whether {@link Slideout.open} should be called immediately from `init`. Default `true`. */
  autoOpen: boolean;
  /** Whether the Escape key should close the slideout. Default `true`. */
  closeOnEsc: boolean;
  /** Whether clicking the shade should close the slideout. Default `true`. */
  closeOnShadeClick: boolean;
  /**
   * The element that should regain focus when the slideout closes. Falls back
   * to `document.activeElement` at {@link Slideout.open} time when unset.
   */
  triggerElement: unknown;
}

/**
 * A slideout panel — the `@craftcms/garnish` `Base` port of the legacy jQuery
 * `Craft.Slideout`. Builds a container element around passed-in contents,
 * manages its shade, open/close transitions, and stacking against any other
 * open slideouts (via the class statics below).
 *
 * @fires open - When the slideout has finished opening (legacy timing/parity).
 * @fires beforeClose - Just before {@link close} starts tearing things down.
 * @fires close - After the close transition finishes.
 *
 * @example
 * ```ts
 * import {Slideout} from '@/modules/slideout';
 *
 * const slideout = new Slideout('<p>Hello</p>', {closeOnEsc: true});
 * slideout.on('close', () => console.log('closed'));
 * ```
 */
export class Slideout extends Base<SlideoutSettings> {
  /** Default {@link SlideoutSettings}, merged with the per-instance overrides. */
  static defaults: SlideoutSettings = {
    containerElement: 'div',
    containerAttributes: {},
    autoOpen: true,
    closeOnEsc: true,
    closeOnShadeClick: true,
    triggerElement: null,
  };

  /** Container-id → instance map (legacy `Craft.Slideout.instances`), populated by {@link init}. */
  static instances: Record<string, Slideout> = {};

  /** Currently-open slideouts, most-recently-opened first (legacy `Craft.Slideout.openPanels`). */
  static openPanels: Slideout[] = [];

  /**
   * The CSS logical-inline-position property a panel's offscreen/onscreen
   * position is animated on — the opposite side of `Craft.slideoutPosition`,
   * because of the direction panels slide in from.
   */
  static positionProp(): string {
    return `inset-inline-${
      Craft.slideoutPosition === 'start' ? 'end' : 'start'
    }`;
  }

  /** The number of currently-open panels. */
  static totalPanels(): number {
    return Slideout.openPanels.length;
  }

  /** Register a newly-opened panel and reposition the stack. */
  static addPanel(panel: Slideout): void {
    Slideout.openPanels.unshift(panel);
    if (panel.useMobileStyles) {
      panel.$container.css('top', 0);
    } else {
      Slideout.updateStyles();
    }
  }

  /**
   * Unregister a closing panel and reposition the remaining stack. Mutates
   * {@link openPanels} in place (`splice`, not a `filter` reassignment) so
   * that `window.Craft.Slideout.openPanels` — mirrored onto the compatified
   * global by `index.ts` — keeps pointing at the live array instead of going
   * stale after the first removal.
   */
  static removePanel(panel: Slideout): void {
    const index = Slideout.openPanels.indexOf(panel);
    if (index !== -1) {
      Slideout.openPanels.splice(index, 1);
    }
    if (panel.useMobileStyles) {
      panel.$container.css('top', '100vh');
    } else {
      panel.$container.css(Slideout.positionProp(), '100vw');
      Slideout.updateStyles();
    }
  }

  /** Reposition every open panel, and toggle the body's `no-scroll` class. */
  static updateStyles(): void {
    const totalPanels = Slideout.totalPanels();
    Slideout.openPanels.forEach((panel, i) => {
      panel.$container.css(
        Slideout.positionProp(),
        `${45 * ((totalPanels - i) / totalPanels)}vw`
      );
    });

    document.body.classList.toggle('no-scroll', totalPanels !== 0);
  }

  // --- Instance state (jQuery-typed; see the class docblock for why) --------

  $outerContainer: any = null;
  $container: any = null;
  $shade: any = null;
  $liveRegion: any = null;
  $triggerElement: any = null;
  isOpen = false;
  isOpening = false;
  useMobileStyles: boolean | null = null;

  /**
   * @param contents - Anything jQuery's `.append()` accepts (an element,
   *   jQuery collection, HTML string, or array of those) to place inside the
   *   generated container.
   * @param settings - Optional settings overrides (see {@link SlideoutSettings}).
   */
  constructor(contents?: unknown, settings?: Partial<SlideoutSettings>) {
    super();
    if (new.target === Slideout) {
      this.init(contents, settings);
    }
  }

  /**
   * Build the container/shade and (by default) open it. Invoked from the
   * constructor only for the leaf class; a `compatify()`-built subclass (e.g.
   * `Craft.CpScreenSlideout`) calls this via `this.base(contents, settings)`
   * from its own `init` — see the class docblock.
   */
  init(contents?: unknown, settings?: Partial<SlideoutSettings>): void {
    this.setSettings(settings, Slideout.defaults);

    this.$outerContainer = $('<div/>', {
      class: 'slideout-container cp-legacy hidden',
    });
    this.$container = $(
      `<${this.settings!.containerElement}/>`,
      this.settings!.containerAttributes
    )
      .attr('data-slideout', '')
      .addClass('slideout cp-legacy')
      .append(contents)
      .data('slideout', this)
      .appendTo(this.$outerContainer);

    containerSlideouts.set(this.$container[0], this);

    if (this.$container.attr('id')) {
      Slideout.instances[this.$container.attr('id')] = this;
    }

    addModalAttributes(this.$outerContainer[0]);

    Craft.trapFocusWithin(this.$container);

    this.$liveRegion = $sharedLiveRegion;
    this.$liveRegion.appendTo(this.$container);

    if (this.settings!.autoOpen) {
      this.open();
    }
  }

  open(): void {
    if (this.isOpen) {
      return;
    }

    this.setTriggerElement(
      this.settings!.triggerElement || document.activeElement
    );
    this._cancelTransitionListeners();

    const activePreview =
      Craft.Preview.getActive() || Craft.LivePreview.getActive();
    this.useMobileStyles = activePreview || Craft.useMobileStyles();

    this.$outerContainer.removeClass('so-mobile so-lp');
    this.$container.removeClass('so-mobile so-lp');

    if (activePreview) {
      this.$outerContainer.addClass('so-lp');
      this.$container.addClass('so-lp');
    } else if (this.useMobileStyles) {
      this.$container.addClass('so-mobile');
    }

    if (activePreview || !this.useMobileStyles) {
      if (!this.$shade) {
        this.$shade = $('<div class="slideout-shade"/>');

        if (this.settings!.closeOnShadeClick) {
          this.addListener(this.$shade, 'click', (ev: any) => {
            ev.stopPropagation();
            this.close();
          });
        }
      }

      // Keep the shade + container at the end of <body> so they get the
      // highest sub-z-indexes.
      this.$shade.appendTo(bod).show();
    } else if (this.$shade) {
      this.$shade.remove();
      this.$shade = null;
    }

    this.$outerContainer.appendTo(bod).removeClass('hidden');

    if (activePreview) {
      // keep the width equal to the edit pane width
      this.updateWidthsForPreviewPane(activePreview);
      let containerWidth = activePreview.$editorContainer.width();
      const resizeObserver = new ResizeObserver(() => {
        if (
          this.isOpen &&
          containerWidth !==
            (containerWidth = activePreview.$editorContainer.width())
        ) {
          this.updateWidthsForPreviewPane(activePreview);
        }
      });
      resizeObserver.observe(activePreview.$editorContainer[0]);
      activePreview.on('beforeClose', () => {
        resizeObserver.disconnect();
      });
    }

    this.isOpening = true;

    if (this.useMobileStyles) {
      this.$container.css({
        top: '100vh',
        [Slideout.positionProp()]: '',
      });
    } else {
      this.$container.css({
        top: '',
        [Slideout.positionProp()]: '100vw',
      });
    }

    this._afterTransition(this.$container, () => {
      this.isOpening = false;
      this.setFocusWithin();
    });

    if (this.$shade) {
      // Force a reflow so the `so-visible` class transitions instead of
      // applying instantly.
      void this.$shade[0].offsetWidth;
      this.$shade.addClass('so-visible');
    }

    // Force a reflow before the position/top change above transitions.
    void this.$container[0].offsetWidth;
    Slideout.addPanel(this);

    this.enable();
    uiLayerManager().addLayer(this.$outerContainer[0]);
    hideBackgroundLayers();

    if (this.settings!.closeOnEsc) {
      uiLayerManager().registerShortcut(ESC_KEY, () => {
        this.close();
      });
    }

    this.isOpen = true;
    this.trigger('open');
  }

  setFocusWithin(): void {
    Craft.setFocusWithin(this.$container);
  }

  updateWidthsForPreviewPane(activePreview: any): void {
    const width = activePreview.$editorContainer.width() - 1;
    if (this.$shade) {
      this.$shade.width(width);
    }
    this.$outerContainer.css('width', `calc(${width}px - var(--m) * 2)`);
  }

  setTriggerElement(trigger: unknown): void {
    this.$triggerElement = $(trigger);
  }

  close(): void {
    if (!this.isOpen) {
      return;
    }

    this.trigger('beforeClose');
    this.disable();
    this.isOpen = false;

    this._cancelTransitionListeners();

    if (this.$shade) {
      this.$shade.removeClass('so-visible');
      this._afterTransition(this.$shade, () => {
        this.$shade.hide();
      });
    }

    Slideout.removePanel(this);
    uiLayerManager().removeLayer();
    resetBackgroundLayerVisibility();
    this._afterTransition(this.$container, () => {
      this.$outerContainer.addClass('hidden');
      this.trigger('close');
    });

    if (this.$triggerElement?.length) {
      let focusTarget = $(this.$triggerElement)[0]; // Ensure we convert from jQuery to DOM element

      // Check if target is still visible
      if (!focusTarget.checkVisibility()) {
        // If it's a disclosure, get the disclosure trigger instead
        const disclosure = focusTarget.closest('.menu--disclosure');
        if (disclosure) {
          const disclosureId = disclosure.getAttribute('id');
          focusTarget = document.querySelector(
            `[aria-controls="${disclosureId}"]`
          );
        } else {
          focusTarget = null;
        }
      }

      if (focusTarget) {
        setTimeout(() => {
          focusTarget.focus();
        }, 150);
      }
    }
  }

  /**
   * Performs the callback after the CSS transition has ended, or immediately
   * if the user prefers reduced motion.
   */
  _afterTransition($target: any, callback: () => void): void {
    if (prefersReducedMotion()) {
      callback();
    } else {
      $target.one('transitionend.slideout', callback);
    }
  }

  _cancelTransitionListeners(): void {
    if (this.$shade) {
      this.$shade.off('transitionend.slideout');
    }

    this.$container.off('transitionend.slideout');
  }

  /**
   * Tear the slideout down: remove the shade/container from the DOM, release
   * the {@link containerSlideouts} WeakMap entry, unregister from
   * {@link Slideout.instances}, then run the `Base` teardown (emits
   * `destroy`, removes tracked listeners).
   */
  override destroy(): void {
    if (this.$shade) {
      this.$shade.remove();
      this.$shade = null;
    }

    this.$outerContainer.remove();

    if (this.$container) {
      containerSlideouts.delete(this.$container[0]);
    }

    this.$outerContainer = null;
    this.$container = null;

    // In-place deletion (not a `Craft.filterObject` reassignment) so
    // `window.Craft.Slideout.instances` — mirrored onto the compatified
    // global by `index.ts` — keeps pointing at the live object.
    for (const key of Object.keys(Slideout.instances)) {
      if (Slideout.instances[key] === this) {
        delete Slideout.instances[key];
      }
    }

    super.destroy();
  }
}

export default Slideout;
