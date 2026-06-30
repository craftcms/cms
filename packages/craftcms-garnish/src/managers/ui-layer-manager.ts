/**
 * UI Layer Manager.
 *
 * Manages the visible UI "layers" — the base document plus any open modals,
 * HUDs, slideouts, or menus. jQuery-free port of the legacy class.
 */

import {Base} from '../base';
import {bod} from '../globals';
import {isCtrlKeyPressed} from '../utils/env';
import {getElement} from '../utils/dom';
import type {UiLayer} from './registry';

export interface ShortcutDef {
  keyCode: number;
  ctrl?: boolean;
  shift?: boolean;
  alt?: boolean;
}

interface NormalizedShortcut {
  keyCode: number;
  ctrl: boolean;
  shift: boolean;
  alt: boolean;
}

interface RegisteredShortcut {
  key: string;
  shortcut: NormalizedShortcut;
  callback: (ev: KeyboardEvent) => void;
}

interface LayerOptions {
  bubble: boolean;
}

interface Layer extends UiLayer {
  $container: Element | null;
  shortcuts: RegisteredShortcut[];
  isModal?: boolean;
  options: LayerOptions;
}

type ShortcutKeyboardEvent = KeyboardEvent & {
  bubbleShortcut?: () => void;
};

/**
 * Manages the stack of visible UI "layers" — the base document plus any open
 * modals, HUDs, slideouts, or menus — and the keyboard shortcuts scoped to each.
 * The active instance is a singleton, created by {@link initGarnish} and exposed
 * as `Garnish.uiLayerManager`; {@link Modal} uses it for layer stacking and its
 * Escape shortcut.
 *
 * Shortcuts are dispatched top-down: a `keydown` is offered to the current
 * layer's shortcuts first, then bubbles to lower layers only when the layer opts
 * in (`options.bubble`) or `ev.bubbleShortcut()` is called.
 */
export class UiLayerManager extends Base {
  /** The layer stack; index `0` is the base document layer. */
  layers: Layer[];

  constructor() {
    super();

    this.layers = [
      {
        $container: bod,
        shortcuts: [],
        options: {bubble: false},
      },
    ];

    this.addListener(bod, 'keydown', 'triggerShortcut');
  }

  /** The index of the topmost layer (`0` = base document). */
  get layer(): number {
    return this.layers.length - 1;
  }

  /** The topmost (active) layer. */
  get currentLayer(): Layer {
    return this.layers[this.layer]!;
  }

  /** All layers flagged as modal. */
  get modalLayers(): Layer[] {
    return this.layers.filter((layer) => layer.isModal === true);
  }

  /** The highest modal layer, or `undefined` if no modal is open. */
  get highestModalLayer(): Layer | undefined {
    return this.modalLayers.pop();
  }

  /**
   * Push a new UI layer onto the stack and emit `addLayer`. A layer whose
   * container has `aria-modal="true"` is flagged as modal.
   *
   * Accepts `(container, options)` or `(options)` (param shift when the first
   * arg is a plain options object).
   *
   * @param container - The layer's container element (or an options object).
   * @param options - Layer options, e.g. `{bubble: true}` to let unhandled
   *   shortcuts fall through to lower layers.
   * @returns `this`, for chaining.
   */
  addLayer(
    container?: Element | Partial<LayerOptions> | null,
    options?: Partial<LayerOptions>
  ): this {
    let containerEl: Element | null = null;

    if (isPlainObject(container)) {
      options = container as Partial<LayerOptions>;
      containerEl = null;
    } else if (container) {
      containerEl = (getElement(container as Element) as Element) ?? null;
    }

    const resolvedOptions: LayerOptions = Object.assign(
      {bubble: false},
      options ?? {}
    );

    this.layers.push({
      $container: containerEl,
      shortcuts: [],
      isModal: containerEl
        ? containerEl.getAttribute('aria-modal') === 'true'
        : false,
      options: resolvedOptions,
    });

    this.trigger('addLayer', {
      layer: this.layer,
      $container: this.currentLayer.$container,
      options: resolvedOptions,
    });

    return this;
  }

  /**
   * Remove a layer and emit `removeLayer`. With no argument, pops the topmost
   * layer; with an element, removes the matching layer wherever it sits.
   *
   * @param layer - The container element of the layer to remove (optional).
   * @returns `this` when the top layer was popped, otherwise `undefined`.
   * @throws If asked to remove the base document layer.
   */
  removeLayer(layer?: Element): this | undefined {
    if (this.layer === 0) {
      throw 'Can’t remove the base layer.';
    }

    if (layer) {
      const layerIndex = this.getLayerIndex(layer);
      if (layerIndex) {
        this.removeLayerAtIndex(layerIndex);
      }
      return undefined;
    }

    this.layers.pop();
    this.trigger('removeLayer');
    return this;
  }

  getLayerIndex(layer: Element): number | undefined {
    const layerEl = getElement(layer) as Element | undefined;
    let layerIndex: number | undefined;

    this.layers.forEach((l, index) => {
      if (
        layerIndex === undefined &&
        l.$container !== null &&
        l.$container === layerEl
      ) {
        layerIndex = index;
      }
    });

    return layerIndex;
  }

  removeLayerAtIndex(index: number): this {
    this.layers.splice(index, 1);
    this.trigger('removeLayer');
    return this;
  }

  /**
   * Register a keyboard shortcut on a layer (the current one by default).
   *
   * @param shortcut - A key code, or a `{keyCode, ctrl?, shift?, alt?}` definition.
   * @param callback - Invoked (after `preventDefault`) when the shortcut matches.
   * @param layer - Layer index to scope the shortcut to; defaults to the current layer.
   * @returns `this`, for chaining.
   *
   * @example
   * ```ts
   * import {Garnish, ESC_KEY} from '@craftcms/garnish';
   * Garnish.uiLayerManager!.registerShortcut(ESC_KEY, () => closeThing());
   * ```
   */
  registerShortcut(
    shortcut: number | ShortcutDef,
    callback: (ev: KeyboardEvent) => void,
    layer?: number
  ): this {
    const normalized = this._normalizeShortcut(shortcut);
    if (layer === undefined) {
      layer = this.layer;
    }
    this.layers[layer]!.shortcuts.push({
      key: JSON.stringify(normalized),
      shortcut: normalized,
      callback,
    });
    return this;
  }

  /**
   * Remove a previously registered shortcut from a layer.
   *
   * @param shortcut - The same key code / definition passed to {@link registerShortcut}.
   * @param layer - Layer index; defaults to the current layer.
   * @returns `this`, for chaining.
   */
  unregisterShortcut(shortcut: number | ShortcutDef, layer?: number): this {
    const normalized = this._normalizeShortcut(shortcut);
    const key = JSON.stringify(normalized);
    if (layer === undefined) {
      layer = this.layer;
    }
    const index = this.layers[layer]!.shortcuts.findIndex((s) => s.key === key);
    if (index !== -1) {
      this.layers[layer]!.shortcuts.splice(index, 1);
    }
    return this;
  }

  private _normalizeShortcut(
    shortcut: number | ShortcutDef
  ): NormalizedShortcut {
    let def: ShortcutDef;
    if (typeof shortcut === 'number') {
      def = {keyCode: shortcut};
    } else {
      def = shortcut;
    }

    if (typeof def.keyCode !== 'number') {
      throw 'Invalid shortcut';
    }

    return {
      keyCode: def.keyCode,
      ctrl: !!def.ctrl,
      shift: !!def.shift,
      alt: !!def.alt,
    };
  }

  triggerShortcut(ev: ShortcutKeyboardEvent, layerIndex?: number): void {
    if (layerIndex === undefined) {
      layerIndex = this.layer;
    }
    const layer = this.layers[layerIndex]!;
    const shortcut = layer.shortcuts.find(
      (s) =>
        s.shortcut.keyCode === ev.keyCode &&
        s.shortcut.ctrl === isCtrlKeyPressed(ev) &&
        s.shortcut.shift === ev.shiftKey &&
        s.shortcut.alt === ev.altKey
    );

    ev.bubbleShortcut = () => {
      if (layerIndex! > 0) {
        this.triggerShortcut(ev, layerIndex! - 1);
      }
    };

    if (shortcut) {
      ev.preventDefault();
      shortcut.callback(ev);
    } else if (layer.options.bubble) {
      ev.bubbleShortcut();
    }
  }
}

function isPlainObject(val: unknown): val is Record<string, unknown> {
  return (
    typeof val === 'object' &&
    val !== null &&
    !(val instanceof Element) &&
    (Object.getPrototypeOf(val) === Object.prototype ||
      Object.getPrototypeOf(val) === null)
  );
}
