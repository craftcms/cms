/**
 * The shared slideout panel stack.
 *
 * Craft has two slideout implementations — the legacy jQuery `Slideout`
 * (`@/modules/slideout`) and the Vue panels in this directory — and both can
 * be open at once: a Vue panel opened over a page a legacy slideout already
 * covers, or the reverse. Anything that has to reason about *every* open
 * panel rather than one implementation's own lives here, so the two interleave
 * correctly:
 *
 * - the stacking offsets, so each panel peeks out from behind the one above it
 * - the single shade, and which panel a click on it dismisses
 * - the body scroll lock
 * - hiding the rest of the page from assistive technology
 * - repositioning any open HUDs
 *
 * Each implementation registers an adapter rather than being ported onto the
 * other: the legacy class keeps its jQuery members and its own chrome, and
 * hands over only the decisions that need a whole-stack view.
 */

import {
  Garnish,
  HUD,
  initGarnish,
  hideModalBackgroundLayers,
  resetModalBackgroundLayerVisibility,
} from '@craftcms/garnish';

import './panel-stack.css';

// Populate `Garnish.uiLayerManager` (idempotent) so it's there the first time
// a panel opens, whether or not anything else has imported
// `@craftcms/garnish/compat` (which also calls this) yet.
initGarnish();

type UiLayerManager = NonNullable<typeof Garnish.uiLayerManager>;

interface LegacyHud {
  showing: boolean;
  updateSizeAndPosition(force: boolean): void;
}

interface LegacyGarnishRuntime {
  uiLayerManager?: UiLayerManager;
  hideModalBackgroundLayers?: () => void;
  resetModalBackgroundLayerVisibility?: () => void;
  HUD?: {instances: LegacyHud[]};
}

function legacyGarnish(): LegacyGarnishRuntime | null {
  return Object.getOwnPropertyDescriptor(window, 'Garnish')?.value ?? null;
}

/**
 * The UI-layer manager slideouts should coordinate with.
 *
 * The legacy garnish bundle instantiates its OWN `Garnish.UiLayerManager`
 * singleton, and every legacy layer consumer (modals, menus, HUDs) registers
 * with it — if slideouts used the modern singleton on those pages, Escape
 * handling and layer stacking would run in two disconnected managers (one
 * Escape press closing both a menu and the slideout behind it, say). Prefer
 * the page's legacy manager when present; fall back to the modern one for
 * legacy-free surfaces.
 */
export function uiLayerManager(): UiLayerManager {
  return legacyGarnish()?.uiLayerManager ?? Garnish.uiLayerManager!;
}

/**
 * Same split-brain concern as {@link uiLayerManager}: the modern
 * `hideModalBackgroundLayers`/`resetModalBackgroundLayerVisibility` utilities
 * keep their own hidden-layer bookkeeping, separate from the legacy bundle's.
 * Use the page's legacy implementations when present so a panel's hide/reset
 * pairs with the modals and HUDs it stacks against.
 */
function hideBackgroundLayers(): void {
  const legacy = legacyGarnish()?.hideModalBackgroundLayers;
  (legacy ?? hideModalBackgroundLayers)();
}

function resetBackgroundLayerVisibility(): void {
  const legacy = legacyGarnish()?.resetModalBackgroundLayerVisibility;
  (legacy ?? resetModalBackgroundLayerVisibility)();
}

/**
 * One open panel, as the stack needs to see it. Implemented by
 * `@/modules/slideout`'s `Slideout` and by `SlideoutPanel.vue`.
 */
export interface StackedPanel {
  /**
   * The panel's outermost element — what the lifecycle events are dispatched
   * on, and what gets a reflow forced before the panel is positioned.
   */
  element: HTMLElement;

  /**
   * Place this panel at `index` (`0` = oldest, furthest back) of `total`.
   *
   * The stack owns the ordering; each implementation renders the offset in its
   * own terms. They don't share one, because they position different things:
   * the legacy stack offsets a `.slideout` inside a full-viewport container,
   * while a Vue panel is positioned directly and its width is configurable.
   */
  position(index: number, total: number): void;

  /**
   * What a click on the shade should do. Usually close — after whatever
   * unsaved-changes check the implementation has.
   */
  handleShadeClick(): void;

  /**
   * Whether this panel wants the shade suppressed. A legacy slideout in
   * mobile layout is a full-screen sheet with nothing left visible to shade.
   *
   * The shade shows if *any* open panel wants it, so a full-screen sheet
   * stacked under a regular panel doesn't hide the shade the panel above
   * needs.
   */
  suppressShade?(): boolean;
}

/** Open panels, oldest first. The last entry is the one on top. */
const panels: StackedPanel[] = [];

/** The open panels, oldest first. */
export function stackedPanels(): readonly StackedPanel[] {
  return panels;
}

/** The panel on top of the stack, or `null` when nothing is open. */
export function topStackedPanel(): StackedPanel | null {
  return panels[panels.length - 1] ?? null;
}

/**
 * Add a panel to the top of the stack.
 *
 * Call this *after* registering the panel's UI layer — {@link
 * hideBackgroundLayers} skips the topmost layer's container, so it needs the
 * layer to already be there to know what to leave alone.
 */
export function registerPanel(panel: StackedPanel): void {
  if (panels.includes(panel)) {
    return;
  }

  panels.push(panel);

  // Force a reflow before the panel is positioned, so it transitions in from
  // wherever it was parked offscreen rather than being painted at its final
  // offset straight away.
  void panel.element.offsetWidth;

  sync();
  hideBackgroundLayers();
  panel.element.dispatchEvent(
    new CustomEvent('craft-show', {bubbles: true, composed: true})
  );
}

/**
 * Remove a panel from the stack.
 *
 * Positioning the panel on its way out is the implementation's job — it knows
 * which direction its own chrome slides away in — so this only reflows the
 * panels that remain.
 */
export function unregisterPanel(panel: StackedPanel): void {
  const index = panels.indexOf(panel);

  if (index === -1) {
    return;
  }

  panels.splice(index, 1);

  sync();
  resetBackgroundLayerVisibility();
  panel.element.dispatchEvent(
    new CustomEvent('craft-hide', {bubbles: true, composed: true})
  );
}

/**
 * Reposition every open panel and bring the rest of the page's state into
 * line. Exported for the legacy `Slideout.updateStyles()` shim, which plugins
 * can still call directly.
 */
export function syncPanelStack(): void {
  sync();
}

function sync(): void {
  const total = panels.length;

  panels.forEach((panel, index) => panel.position(index, total));

  document.body.classList.toggle('no-scroll', total !== 0);

  syncShade();
  repositionHuds();
}

// --- Shade ----------------------------------------------------------------

let shade: HTMLElement | null = null;

/**
 * The shared shade element, created on first use.
 *
 * Exposed because a legacy slideout inside a live preview narrows the shade to
 * the width of the edit pane (`Slideout.updateWidthsForPreviewPane()`); the
 * inline width it sets is cleared again when the stack empties.
 */
export function shadeElement(): HTMLElement {
  if (!shade) {
    shade = document.createElement('div');
    shade.className = 'cp-slideout-shade';

    // Bound once, on the shared element, and dispatched to whichever panel is
    // currently on top. Per-panel listeners would all fire at once and close
    // the whole stack.
    shade.addEventListener('click', (event) => {
      event.stopPropagation();
      topStackedPanel()?.handleShadeClick();
    });
  }

  // Re-append rather than append-once: the shade is left in the document
  // between slideouts (see the stylesheet), so anything that replaces the
  // body's contents underneath it — a full page load, a test resetting the
  // DOM — would otherwise strand this element and leave every later slideout
  // without a shade.
  if (!shade.isConnected) {
    document.body.appendChild(shade);

    // Reflow, so the next open transitions from transparent instead of
    // appearing fully opaque the moment the class lands.
    void shade.offsetWidth;
  }

  return shade;
}

function syncShade(): void {
  const el = shadeElement();

  if (panels.some((panel) => !panel.suppressShade?.())) {
    el.classList.add('is-visible');

    return;
  }

  el.classList.remove('is-visible');
  // Don't leak a live-preview panel's narrowed shade onto the next slideout.
  el.style.width = '';
}

// --- HUDs -----------------------------------------------------------------

/**
 * Reposition any open HUD whenever the stack changes — panels move, and a HUD
 * anchored to something inside one has to follow.
 */
function repositionHuds(): void {
  // The legacy garnish bundle keeps its own `Garnish.HUD` instance registry,
  // separate from the modern class's — sweep both.
  const legacyInstances = legacyGarnish()?.HUD?.instances ?? [];

  for (const hud of [...HUD.instances, ...legacyInstances]) {
    if (hud.showing) {
      hud.updateSizeAndPosition(true);
    }
  }
}
