/**
 * @craftcms/garnish — modern, tree-shakeable TypeScript rewrite of Craft CMS's
 * Garnish UI library. jQuery-free, ESM-only.
 *
 * Exposes BOTH tree-shakeable named exports (preferred for new code) and a
 * legacy-shaped `Garnish` namespace object (+ `initGarnish()`) for incremental
 * migration and for the compat layer to extend.
 *
 * The core deliberately does NOT: assign `window.Garnish`, add `$.fn` sugar, or
 * pull in jQuery — those are the compat layer's job.
 */

import {Base} from './base';
import {
  ClassEventBus,
  EventEmitter,
  formatDomEvents,
  parseEvents,
  type GarnishEvent,
  type GarnishEventHandler,
} from './events';
import {
  DomListenerRegistry,
  type DomListenerOptions,
  type ElementInput,
} from './dom-listeners';
import {
  installActivate,
  installResize,
  installTextchange,
} from './custom-events';
import {EscManager} from './managers/esc-manager';
import {UiLayerManager} from './managers/ui-layer-manager';
import {setUiLayerManager} from './managers/registry';
import {Modal, type ModalSettings} from './modal';
import {HUD, type HUDSettings} from './hud';
import {DisclosureMenu, type DisclosureMenuSettings} from './disclosure-menu';
import {CustomSelect, type CustomSelectSettings} from './custom-select';
import {MenuBtn, type MenuBtnSettings} from './menu-btn';
import {BaseDrag, type BaseDragSettings} from './drag/base-drag';
import {Drag, type DragSettings} from './drag/drag';
import {DragDrop, type DragDropSettings} from './drag/drag-drop';
import {DragSort, type DragSortSettings} from './drag/drag-sort';
import {DragMove} from './drag-move';
import {Select, type SelectSettings} from './select';
import {ResizeHandle} from './icons/resize-handle';
import {garnishClassBus, globals, win, doc, bod} from './globals';
import type {Callback, Constructor, GarnishBaseSettings} from './types';

import * as constants from './constants';
import * as utils from './utils';

// --- Named exports (preferred for new code) ---------------------------------

export {Base};
export type {Callback, GarnishBaseSettings, ElementInput};
export {
  EventEmitter,
  ClassEventBus,
  parseEvents,
  formatDomEvents,
  type GarnishEvent,
  type GarnishEventHandler,
};
export {DomListenerRegistry, type DomListenerOptions};
export {installActivate, installTextchange, installResize};
export type {TextchangeOptions, ActivateOptions} from './custom-events';
export {EscManager};
export {UiLayerManager};
export {Modal, type ModalSettings};
export {HUD, type HUDSettings};
export type {HUDOrientation, HUDBodyContents} from './hud';
export {DisclosureMenu, type DisclosureMenuSettings};
export type {
  DisclosureMenuItem,
  DisclosureMenuItemConfig,
} from './disclosure-menu';
export {CustomSelect, type CustomSelectSettings};
export {MenuBtn, type MenuBtnSettings};
export {BaseDrag, type BaseDragSettings};
export {Drag, type DragSettings};
export {DragDrop, type DragDropSettings};
export {DragSort, type DragSortSettings};
export {DragMove};
export {Select, type SelectSettings};
export type {SelectHandle, SelectFilter} from './select';
export {ResizeHandle};
export {win, doc, bod};

export * from './utils';
export * from './constants';

export const VERSION = '0.0.0';

// --- Legacy-shaped namespace object -----------------------------------------

/**
 * Class-level event bus proxies. Legacy `Garnish.on/off/once` register
 * class-level handlers dispatched via instanceof in `Base.trigger`.
 */
function on(
  target: Constructor,
  events: string,
  data: Record<string, unknown> | GarnishEventHandler,
  handler?: GarnishEventHandler
): void {
  (garnishClassBus.on as (...a: unknown[]) => void)(
    target,
    events,
    data,
    handler
  );
}
function off(
  target: Constructor,
  events: string,
  handler?: GarnishEventHandler
): void {
  garnishClassBus.off(target, events, handler);
}
function once(
  target: Constructor,
  events: string,
  data: Record<string, unknown> | GarnishEventHandler,
  handler?: GarnishEventHandler
): void {
  (garnishClassBus.once as (...a: unknown[]) => void)(
    target,
    events,
    data,
    handler
  );
}

/**
 * The legacy-shaped `Garnish` namespace object — a single value carrying every
 * Garnish class, constant, utility, and feature flag, mirroring the legacy
 * `window.Garnish` singleton.
 *
 * This is provided for incremental migration and is what the
 * `@craftcms/garnish/compat` layer wraps and assigns to `window.Garnish`. **New
 * code should prefer the tree-shakeable named exports** (e.g.
 * `import {Modal, trapFocusWithin} from '@craftcms/garnish'`) — importing this
 * object pulls in the whole surface.
 *
 * The manager singletons (`escManager`, `uiLayerManager`) are `undefined` until
 * {@link initGarnish} is called.
 */
export const Garnish = {
  // constants
  ...constants,

  // native globals (compat adds the jQuery-wrapped $win/$doc/$bod forms)
  win,
  doc,
  bod,
  get scrollContainer() {
    return globals.scrollContainer;
  },
  set scrollContainer(value: EventTarget) {
    globals.scrollContainer = value;
  },
  get rtl() {
    return globals.rtl;
  },
  get ltr() {
    return globals.ltr;
  },
  get activateEventsMuted() {
    return globals.activateEventsMuted;
  },
  set activateEventsMuted(value: boolean) {
    globals.activateEventsMuted = value;
  },
  get resizeEventsMuted() {
    return globals.resizeEventsMuted;
  },
  set resizeEventsMuted(value: boolean) {
    globals.resizeEventsMuted = value;
  },

  // classes
  Base,
  EscManager,
  UiLayerManager,
  Modal,
  HUD,
  DisclosureMenu,
  CustomSelect,
  MenuBtn,
  BaseDrag,
  Drag,
  DragDrop,
  DragSort,
  DragMove,
  Select,
  /** @deprecated Use UiLayerManager instead. */
  ShortcutManager: UiLayerManager,

  // class-level event bus
  on,
  off,
  once,

  // event parser (legacy alias)
  _normalizeEvents: (events: string | string[]) =>
    parseEvents(events).map((e) => [e.type, e.namespace ?? undefined]),

  // custom-event installers
  installActivate,
  installTextchange,
  installResize,

  // icons
  ResizeHandle,

  // utilities (every §4 member that survives in core)
  ...utils,
  muteResizeEvents,

  // managers (attached by initGarnish)
  escManager: undefined as EscManager | undefined,
  uiLayerManager: undefined as UiLayerManager | undefined,
};

/**
 * Suppress Garnish `resize` custom events while `callback` runs, then restore
 * the previous muted state. Useful when programmatically resizing elements that
 * have `resize` listeners and you don't want them to fire.
 *
 * @param callback - The work to run with resize events muted.
 */
function muteResizeEvents(callback: () => void): void {
  const prior = globals.resizeEventsMuted;
  globals.resizeEventsMuted = true;
  callback();
  globals.resizeEventsMuted = prior;
}

export {muteResizeEvents};

/**
 * Lazily instantiate the manager singletons (`escManager`, `uiLayerManager`)
 * and attach them to the {@link Garnish} namespace, registering the UI layer
 * manager so layer-aware components (like {@link Modal}) can find it. Idempotent.
 *
 * The compat layer calls this during install; call it yourself if you use the
 * `Garnish` namespace object directly and need the managers populated.
 *
 * @returns The {@link Garnish} namespace, with managers attached.
 */
export function initGarnish(): typeof Garnish {
  if (!Garnish.escManager) {
    Garnish.escManager = new EscManager();
  }
  if (!Garnish.uiLayerManager) {
    Garnish.uiLayerManager = new UiLayerManager();
    setUiLayerManager(Garnish.uiLayerManager);
  }
  return Garnish;
}

export default Garnish;
