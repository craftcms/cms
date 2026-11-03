/**
 * Slideout — a slide-in panel: builds a container around passed-in contents
 * and manages its shade, open/close transitions, dismissal, focus, and
 * stacking against other open panels. See the module README for usage.
 *
 * Two structural constraints shape this class:
 *
 * - **Legacy subclasses exist.** `Craft.AuthMethodSetup.Slideout` (and, one
 *   level down, `Craft.ElementEditorSlideout`) subclass the `Craft.Slideout`
 *   global via `Garnish.Base.extend()`, calling `this.base(contents,
 *   settings)` from their own `init`. So {@link init} is a real public
 *   method, invoked from the constructor only for the leaf class (the
 *   `new.target === Slideout` guard below), and `index.ts` assigns the
 *   global as `compatify(Slideout)` so `.extend()`/`this.base()` dispatch
 *   into it.
 * - **The `$`-prefixed members are public API.** External code reads them
 *   directly (`ElementEditor.js`'s `this.$container.data('slideout')`,
 *   `CpScreenSlideout`'s chrome), so the names stay. Most are still jQuery
 *   collections; {@link Slideout.$liveRegion} is a plain element, matching the
 *   ported Garnish `Modal`.
 *   `declare const $` / `declare const Craft` are page globals.
 */

import {
  Base,
  ESC_KEY,
  bod,
  addModalAttributes,
  prefersReducedMotion,
  setFocusWithin,
  isMobileBrowser,
  trapFocusWithin,
  type GarnishBaseSettings,
} from '@craftcms/garnish';
import {announce} from '@/common/announcer';
import {
  registerPanel,
  shadeElement,
  syncPanelStack,
  uiLayerManager,
  unregisterPanel,
  type StackedPanel,
} from '@/common/slideouts/panel-stack';
import {
  getActivePreview,
  previewEditorOffset,
} from '@/modules/elements/live-preview/previews';
import {containerSlideouts} from './support';
import {setElementAttributes} from '@/common/craft/attributes';
import {resolveElement} from '@/common/utils/dom';
import {jq} from '@/common/utils/jquery';

declare const Craft: any;

// Re-exported because `cp-screen-slideout.ts` and legacy consumers reach for
// it here; the lookup itself lives with the rest of the cross-stack
// coordination.
export {uiLayerManager};

/**
 * A single status live region shared by every slideout — it moves (not
 * clones) into whichever container most recently initialized, since screen
 * readers only announce one status region at a time.
 *
 * Built with the DOM API rather than `$('<span …>')` so that merely importing
 * this module doesn't need jQuery: `cp.ts` pulls it in on every Inertia CP
 * page, including ones that never load the legacy bundle, and a bare `$` at
 * module scope would throw there and abort the whole CP bootstrap. Same
 * construction as the modern Garnish `Modal` port's live region.
 */
const sharedLiveRegion = document.createElement('span');
sharedLiveRegion.className = 'visually-hidden';
sharedLiveRegion.setAttribute('role', 'status');

/**
 * Settings accepted by {@link Slideout}. Pass a `Partial<SlideoutSettings>` to
 * the constructor; unset keys fall back to {@link Slideout.defaults}.
 */
export interface SlideoutSettings extends GarnishBaseSettings {
  /** Tag name for the generated container element. Default `'div'`. */
  containerElement: string;
  /** Attributes applied to the container element. */
  containerAttributes: Record<string, string | number | boolean>;
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
  triggerElement: SlideoutTrigger;
}

type SlideoutContents = string | Node | ArrayLike<Node> | null;
type SlideoutTrigger = string | Element | ArrayLike<Element> | null;

function contentNodes(contents: SlideoutContents | undefined): Node[] {
  if (contents == null) {
    return [];
  }
  if (typeof contents === 'string') {
    const template = document.createElement('template');
    template.innerHTML = contents;
    return Array.from(template.content.childNodes);
  }
  if (contents instanceof Node) {
    return [contents];
  }
  return Array.from(contents);
}

/**
 * A slideout panel. Builds a container element around passed-in contents,
 * manages its shade, open/close transitions, and stacking against any other
 * open slideouts (via the class statics below).
 *
 * @fires open - When the slideout has opened.
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
export class Slideout extends Base<SlideoutSettings> implements StackedPanel {
  /** Default {@link SlideoutSettings}, merged with the per-instance overrides. */
  static defaults: SlideoutSettings = {
    containerElement: 'div',
    containerAttributes: {},
    autoOpen: true,
    closeOnEsc: true,
    closeOnShadeClick: true,
    triggerElement: null,
  };

  /** Container-id → instance map (also reachable as `Craft.Slideout.instances`), populated by {@link init}. */
  static instances: Record<string, Slideout> = {};

  /** Currently-open slideouts, most-recently-opened first (also reachable as `Craft.Slideout.openPanels`). */
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
    registerPanel(panel);
  }

  /**
   * Unregister a closing panel and reposition the remaining stack. Must
   * mutate {@link openPanels} in place — `Craft.Slideout.openPanels` shares
   * the array reference (see `index.ts`), so a reassignment would strand it.
   */
  static removePanel(panel: Slideout): void {
    const index = Slideout.openPanels.indexOf(panel);
    if (index !== -1) {
      Slideout.openPanels.splice(index, 1);
    }

    // Park it offscreen on the way out. The shared stack repositions what's
    // left, but a panel it no longer knows about has to see itself off.
    if (panel._inPreview) {
      panel.container!.style.insetInlineStart = '100%';
    } else if (panel.useMobileStyles) {
      panel.container!.style.top = '100vh';
    } else {
      panel.container!.style.setProperty(Slideout.positionProp(), '100vw');
    }

    unregisterPanel(panel);
  }

  /**
   * Reposition every open panel and re-sync the body scroll lock.
   *
   * Both are now the shared stack's job — a Vue slideout can be open at the
   * same time as this one, and only the shared stack sees both. Kept as a
   * delegating shim because `Craft.Slideout.updateStyles()` is public API.
   */
  static updateStyles(): void {
    syncPanelStack();
  }

  // --- Instance state ----------------------------------------------------

  outerContainer: HTMLElement | null = null;
  container: HTMLElement | null = null;
  shade: HTMLElement | null = null;
  $liveRegion: HTMLElement | null = null;
  triggerElement: HTMLElement | null = null;

  isOpen = false;
  isOpening = false;
  useMobileStyles: boolean | null = null;

  /** Whether this opened inside a live preview pane. See {@link open}. */
  _inPreview = false;

  /** Fallback timeouts scheduled by {@link _afterTransition}, in case their transitionend event never fires. */
  _transitionTimeouts: Set<ReturnType<typeof setTimeout>> = new Set();
  _transitionListeners = new Map<(ev: Event) => void, HTMLElement>();

  /** Stops sizing this to Live Preview's editor pane. See {@link open}. */
  _stopFollowingPreview: (() => void) | null = null;

  // --- Shared panel stack (see `@/common/slideouts/panel-stack`) -----------

  /** The element the stack moves, reflows, and fires lifecycle events on. */
  get element(): HTMLElement {
    return this.container!;
  }

  /**
   * Place this panel at `index` of `total`, counting from the oldest.
   *
   * `45` is `100 - 55` — the viewport left over beside a 55%-wide panel. The
   * newest panel (`index + 1 === total`) ends up flush against the trailing
   * edge and each one below it peeks out a little further, so the stack never
   * runs off the screen however deep it goes.
   */
  position(index: number, total: number): void {
    if (this._inPreview) {
      // Over a preview's editor, each covers the one before it.
      this.container!.style.insetInlineStart = '0';

      return;
    }

    if (this.useMobileStyles) {
      // A full-screen sheet slides up from the bottom; there's no room beside
      // it to stagger anything into.
      this.container!.style.top = '0';

      return;
    }

    this.container!.style.setProperty(
      Slideout.positionProp(),
      `${45 * ((index + 1) / total)}vw`
    );
  }

  /**
   * What a click on the shared shade should do.
   *
   * Overridden by `CpScreenSlideout`, which checks for unsaved changes first.
   */
  handleShadeClick(): void {
    if (this.settings!.closeOnShadeClick) {
      this.close();
    }
  }

  /**
   * A mobile-layout slideout covers the viewport, so there's nothing left to
   * shade behind it — unless it's in a live preview pane, where it only
   * covers the editor half.
   */
  suppressShade(): boolean {
    return this.useMobileStyles === true && !this._inPreview;
  }

  /**
   * @param contents - Anything jQuery's `.append()` accepts (an element,
   *   jQuery collection, HTML string, or array of those) to place inside the
   *   generated container.
   * @param settings - Optional settings overrides (see {@link SlideoutSettings}).
   */
  constructor(
    contents?: SlideoutContents,
    settings?: Partial<SlideoutSettings>
  ) {
    super();
    if (new.target === Slideout) {
      this.init(contents, settings);
    }
  }

  /**
   * Build the container/shade and (by default) open it. Invoked from the
   * constructor only for the leaf class; subclasses call it themselves —
   * `super.init()` from TypeScript, or `this.base()` from a legacy
   * `.extend()` body (see the class docblock).
   */
  init(
    contents?: SlideoutContents,
    settings?: Partial<SlideoutSettings>
  ): void {
    this.setSettings(settings, Slideout.defaults);

    const outerContainer = document.createElement('div');
    outerContainer.className = 'slideout-container cp-legacy hidden';
    this.outerContainer = outerContainer;

    const container = document.createElement(this.settings!.containerElement);
    setElementAttributes(container, this.settings!.containerAttributes);
    container.setAttribute('data-slideout', '');
    container.classList.add('slideout', 'cp-legacy');
    container.append(...contentNodes(contents));
    outerContainer.append(container);
    this.container = container;
    jq()?.(container).data('slideout', this);

    containerSlideouts.set(container, this);

    if (container.id) {
      Slideout.instances[container.id] = this;
    }

    addModalAttributes(outerContainer);

    trapFocusWithin(container);

    // `appendChild` moves the one shared node, same as `appendTo` did.
    this.$liveRegion = sharedLiveRegion;
    container.appendChild(this.$liveRegion);

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

    const previewEditor = getActivePreview()?.editor.value ?? null;
    this._inPreview = !!previewEditor;
    this.useMobileStyles =
      !!previewEditor || isMobileBrowser() || document.body.clientWidth < 600;

    const outerContainer = this.outerContainer!;
    const container = this.container!;
    outerContainer.classList.remove('so-mobile', 'so-lp');
    container.classList.remove('so-mobile', 'so-lp');

    if (previewEditor) {
      outerContainer.classList.add('so-lp');
      container.classList.add('so-lp');
    } else if (this.useMobileStyles) {
      container.classList.add('so-mobile');
    }

    // One shade, shared with every other open panel — Vue ones included —
    // and owned by the panel stack, which shows it, hides it, and routes
    // clicks on it to the topmost panel's `handleShadeClick()`. Kept on
    // `shade` because `updateWidthsForPreviewPane()` resizes it.
    this.shade = shadeElement();

    bod.append(outerContainer);
    outerContainer.classList.remove('hidden');

    if (previewEditor) {
      // keep the width equal to the edit pane width
      this.updateWidthsForPreviewPane(previewEditor);
      let containerWidth = previewEditor.offsetWidth;
      const resizeObserver = new ResizeObserver(() => {
        if (
          this.isOpen &&
          containerWidth !== (containerWidth = previewEditor.offsetWidth)
        ) {
          this.updateWidthsForPreviewPane(previewEditor);
        }
      });
      resizeObserver.observe(previewEditor);
      const preview = getActivePreview()!;
      const stopListening = preview.on('before-close', () =>
        this._stopFollowingPreview?.()
      );
      preview.editorCovers.value++;
      this._stopFollowingPreview = () => {
        resizeObserver.disconnect();
        stopListening();
        preview.editorCovers.value--;
        this._stopFollowingPreview = null;
      };
    }

    this.isOpening = true;

    if (this._inPreview) {
      container.style.top = '';
      container.style.insetInlineStart = '100%';
    } else if (this.useMobileStyles) {
      container.style.top = '100vh';
      container.style.setProperty(Slideout.positionProp(), '');
    } else {
      container.style.top = '';
      container.style.setProperty(Slideout.positionProp(), '100vw');
    }

    this._afterTransition(container, () => {
      this.isOpening = false;
      this.setFocusWithin();
    });

    this.enable();

    // Before `addPanel`: registering with the stack is what hides the rest of
    // the page from assistive technology, and that needs this layer already
    // in place to know which container to leave alone.
    uiLayerManager().addLayer(outerContainer);

    // Positions the panel (transitioning it in from the offscreen position
    // set above), shows the shade, and locks body scroll.
    Slideout.addPanel(this);

    if (this.settings!.closeOnEsc) {
      uiLayerManager().registerShortcut(ESC_KEY, () => {
        this.close();
      });
    }

    this.isOpen = true;
    this.trigger('open');
  }

  setFocusWithin(): void {
    setFocusWithin(this.container!);
  }

  /** Sizes the slideout to Live Preview's editor pane, which it covers. */
  updateWidthsForPreviewPane(editor: HTMLElement): void {
    const width = editor.offsetWidth;
    const start = previewEditorOffset(editor);
    if (this.shade) {
      this.shade.style.width = `${width}px`;
      this.shade.style.insetInlineStart = `${start}px`;
    }
    this.outerContainer!.style.width = `${width}px`;
    this.outerContainer!.style.setProperty('--lp-editor-start', `${start}px`);
  }

  setTriggerElement(trigger: SlideoutTrigger): void {
    this.triggerElement =
      typeof trigger === 'string'
        ? document.querySelector<HTMLElement>(trigger)
        : resolveElement(trigger);
  }

  close(): void {
    if (!this.isOpen) {
      return;
    }

    this.trigger('beforeClose');
    this.disable();
    this.isOpen = false;
    this._stopFollowingPreview?.();

    this._cancelTransitionListeners();

    // By element, not the bare pop: with two slideout stacks interleaving,
    // this panel isn't necessarily the topmost layer any more. Before
    // `removePanel`, which is what restores the background layers — it reads
    // the highest remaining modal layer to decide what to un-hide.
    uiLayerManager().removeLayer(this.outerContainer!);

    // Hides the shade if this was the last panel open, and repositions the
    // rest of the stack.
    Slideout.removePanel(this);
    this.shade = null;

    this._afterTransition(this.container!, () => {
      this.outerContainer?.classList.add('hidden');
      this.trigger('close');
    });

    if (this.triggerElement) {
      let focusTarget: HTMLElement | null = this.triggerElement;

      // Check if target is still visible
      if (!focusTarget.checkVisibility()) {
        // If it's in a menu, return focus to the menu's trigger instead
        const actionMenu = focusTarget.closest('craft-action-menu');
        const disclosure = focusTarget.closest('.menu--disclosure');
        if (actionMenu) {
          focusTarget = actionMenu.querySelector<HTMLElement>(
            ':scope > [slot="invoker"]'
          );
        } else if (disclosure) {
          const disclosureId = disclosure.getAttribute('id');
          focusTarget = document.querySelector<HTMLElement>(
            `[aria-controls="${disclosureId}"]`
          );
        } else {
          focusTarget = null;
        }
      }

      if (focusTarget) {
        setTimeout(() => {
          focusTarget!.focus();
        }, 150);
      }
    }
  }

  /**
   * Performs the callback after the CSS transition has ended, or after its
   * expected duration if no event is emitted, or immediately if the user
   * prefers reduced motion.
   */
  _afterTransition(target: HTMLElement, callback: () => void): void {
    // If a user prefers reduced motion, perform the callback immediately
    if (prefersReducedMotion()) {
      callback();
      return;
    }

    const styles = getComputedStyle(target);
    const properties = styles.transitionProperty
      .split(',')
      .map((property) => property.trim());
    const durations = styles.transitionDuration
      .split(',')
      .map(Slideout._timeToMilliseconds);
    const delays = styles.transitionDelay
      .split(',')
      .map(Slideout._timeToMilliseconds);
    let transitionDuration = 0;

    properties.forEach((property, i) => {
      if (property !== 'none') {
        transitionDuration = Math.max(
          transitionDuration,
          (durations[i % durations.length] ?? 0) +
            (delays[i % delays.length] ?? 0)
        );
      }
    });

    if (transitionDuration <= 0) {
      callback();
      return;
    }

    let timeout: ReturnType<typeof setTimeout>;
    const complete = () => {
      if (!this._transitionTimeouts.has(timeout)) {
        return;
      }

      clearTimeout(timeout);
      this._transitionTimeouts.delete(timeout);
      target.removeEventListener('transitionend', onTransitionEnd);
      this._transitionListeners.delete(onTransitionEnd);
      callback();
    };
    const onTransitionEnd = (ev: Event) => {
      if (ev.target === target) {
        complete();
      }
    };

    target.addEventListener('transitionend', onTransitionEnd);
    this._transitionListeners.set(onTransitionEnd, target);
    timeout = setTimeout(complete, transitionDuration + 50);
    this._transitionTimeouts.add(timeout);
  }

  static _timeToMilliseconds(time: string): number {
    const value = parseFloat(time);
    return time.trim().endsWith('ms') ? value : value * 1000;
  }

  _cancelTransitionListeners(): void {
    this._transitionTimeouts.forEach(clearTimeout);
    this._transitionTimeouts.clear();

    for (const [listener, target] of this._transitionListeners) {
      target.removeEventListener('transitionend', listener);
    }
    this._transitionListeners.clear();
  }

  /**
   * Tear the slideout down: remove the shade/container from the DOM, release
   * the {@link containerSlideouts} WeakMap entry, unregister from
   * {@link Slideout.instances}, then run the `Base` teardown (emits
   * `destroy`, removes tracked listeners).
   */
  override destroy(): void {
    this._cancelTransitionListeners();

    // Only drop the reference — the shade is shared with every other panel
    // and belongs to the stack, so removing it would take it away from them.
    this.shade = null;

    this.outerContainer?.remove();

    if (this.container) {
      containerSlideouts.delete(this.container);
    }

    this.outerContainer = null;
    this.container = null;

    // Must delete in place — `Craft.Slideout.instances` shares this object
    // reference (see `index.ts`), so a reassignment would strand it.
    for (const key of Object.keys(Slideout.instances)) {
      if (Slideout.instances[key] === this) {
        delete Slideout.instances[key];
      }
    }

    super.destroy();
  }
}

export default Slideout;
