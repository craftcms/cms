import {
  computed,
  inject,
  shallowReactive,
  shallowRef,
  type ComputedRef,
  type InjectionKey,
  type ShallowRef,
} from 'vue';
import type {DetailsPanel} from '@/common/components/detailsPanels';

/**
 * Opens and closes panels for the screen's details column, which carries it
 * out once it's mounted.
 */
export interface ScreenPanelsController {
  open(panelId: string, withFocus: boolean): void;
  close(panelId: string, returnFocus: boolean): void;
}

/**
 * The panels in a screen's details column, and which one is open. Each screen
 * has its own: a page's, and one for each slideout. Pages add theirs with
 * `ScreenPanel` while they're mounted; the shell can add panels that stay
 * from page to page.
 */
export interface ScreenPanels {
  /** The panels, in rail order. */
  readonly panels: ComputedRef<readonly DetailsPanel[]>;
  /** The open panel, or `null` when the column is folded to its rail. */
  readonly openPanelId: ShallowRef<string | null>;
  /**
   * Counts the times a page’s panels have arrived after there were none,
   * which is when a new page’s panels are shown, so the column opens on
   * them as it would on a fresh page.
   */
  readonly pageGeneration: Readonly<ShallowRef<number>>;
  /** Where each panel goes, by panel id. */
  readonly sections: Map<string, HTMLElement>;
  /** Adds a panel, or updates the one with its id. */
  register(panel: DetailsPanel): void;
  unregister(panelId: string): void;
  /**
   * Opens a panel once it’s in the column. Focus moves into it, unless the
   * caller keeps it where it is, such as when a panel is restored on load.
   */
  open(panelId: string, options?: {withFocus?: boolean}): void;
  /**
   * Folds the column, if the panel is the open one. Focus goes to the panel’s
   * trigger, unless the caller keeps it, such as a button that toggles it.
   */
  close(panelId: string, options?: {returnFocus?: boolean}): void;
  /** Set by the details column while it's mounted. */
  attach(controller: ScreenPanelsController | null): void;
}

export function createScreenPanels(): ScreenPanels {
  const registered = shallowReactive(new Map<string, DetailsPanel>());
  const openPanelId = shallowRef<string | null>(null);
  const pageGeneration = shallowRef(0);
  let controller: ScreenPanelsController | null = null;
  /** A panel to open once the column is mounted and lists it. */
  let pendingOpen: {panelId: string; withFocus: boolean} | null = null;

  function hasPagePanels(): boolean {
    return [...registered.values()].some((panel) => !panel.persistent);
  }

  function flushPendingOpen(): void {
    if (controller && pendingOpen && registered.has(pendingOpen.panelId)) {
      const {panelId, withFocus} = pendingOpen;
      pendingOpen = null;
      controller.open(panelId, withFocus);
    }
  }

  return {
    panels: computed(() =>
      [...registered.values()].sort(
        (first, second) => (first.order ?? 0) - (second.order ?? 0)
      )
    ),
    openPanelId,
    pageGeneration,
    sections: shallowReactive(new Map<string, HTMLElement>()),

    register(panel) {
      if (!panel.persistent && !registered.has(panel.id) && !hasPagePanels()) {
        pageGeneration.value++;
      }

      registered.set(panel.id, panel);
      flushPendingOpen();
    },

    unregister(panelId) {
      registered.delete(panelId);
    },

    open(panelId, {withFocus = true} = {}) {
      pendingOpen = {panelId, withFocus};
      flushPendingOpen();
    },

    close(panelId, {returnFocus = true} = {}) {
      if (pendingOpen?.panelId === panelId) {
        pendingOpen = null;
      }

      controller?.close(panelId, returnFocus);
    },

    attach(next) {
      controller = next;
      flushPendingOpen();
    },
  };
}

/**
 * The id of the panel a screen makes for content a page puts in its
 * `content-details` slot, when it has panels as well.
 */
export const DETAILS_PANEL_ID = 'details';

/** The page’s panels, which persist from page to page, as the page shell does. */
export const screenPanels = createScreenPanels();

/** The nearest screen's panels: the page's, or a slideout's. */
export const ScreenPanelsKey: InjectionKey<ScreenPanels> =
  Symbol('screenPanels');

export function useScreenPanels(): ScreenPanels | null {
  return inject(ScreenPanelsKey, null);
}
