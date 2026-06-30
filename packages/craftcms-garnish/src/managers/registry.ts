/**
 * Manager singleton registry.
 *
 * Breaks the circular dependency between `utils/aria.ts` (which needs the active
 * UI layer manager) and `managers/ui-layer-manager.ts`. `initGarnish()` (or the
 * compat layer) registers the live instance here.
 */

/** Minimal shape `utils/aria.ts` needs from the UI layer manager. */
export interface UiLayer {
  $container: Element | null;
  isModal?: boolean;
}

export interface UiLayerManagerLike {
  readonly currentLayer: UiLayer;
  readonly highestModalLayer: UiLayer | undefined;
}

/**
 * The fuller manager surface Modal (and other layer-using components) need:
 * layer registration plus shortcut registration. Kept structural so the
 * registry has no hard dependency on the concrete `UiLayerManager` class
 * (avoids a circular import with the `index.ts` barrel).
 */
export interface UiLayerManagerFull extends UiLayerManagerLike {
  addLayer(
    container?: Element | Record<string, unknown> | null,
    options?: Record<string, unknown>
  ): unknown;
  removeLayer(layer?: Element): unknown;
  registerShortcut(
    shortcut: number | {keyCode: number},
    callback: (ev: KeyboardEvent) => void,
    layer?: number
  ): unknown;
  unregisterShortcut(
    shortcut: number | {keyCode: number},
    layer?: number
  ): unknown;
}

let uiLayerManager: UiLayerManagerFull | undefined;

export function setUiLayerManager(
  manager: UiLayerManagerFull | undefined
): void {
  uiLayerManager = manager;
}

export function getUiLayerManager(): UiLayerManagerFull | undefined {
  return uiLayerManager;
}
