import {
  computed,
  inject,
  readonly,
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
  readonly openPanelId: Readonly<ShallowRef<string | null>>;
  /**
   * Counts the times a page’s panels have arrived after there were none,
   * which is when a new page’s panels are shown, so the column opens on
   * them as it would on a fresh page.
   */
  readonly pageGeneration: Readonly<ShallowRef<number>>;
  /** Where each panel goes, by panel id. */
  readonly sections: ReadonlyMap<string, HTMLElement>;
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

  // The details column's side: it renders the panels and carries out the
  // policy below, which decides which one is open.

  /** Records, or forgets, the element a panel goes in. */
  setSection(panelId: string, element: HTMLElement | null): void;
  /**
   * Opens the column as it would on a fresh page. `preferred` is the panel the
   * column would rather open on — a remembered one or the URL hash's — `null`
   * to stay folded, or `undefined` for the default.
   */
  start(preferred: PreferredPanel): void;
  /** Opens the column on a new page's panels, folded if it's overlaid. */
  startPage(preferred: PreferredPanel, overlaid: boolean): void;
  /**
   * Follows the panels as they come and go: an open panel that goes away
   * falls back to the page's first, and until one is opened on purpose the
   * column follows the page's first as panels arrive in turn.
   */
  reconcile(preferred: PreferredPanel): void;
  /** Opens a panel on purpose: by the person, the URL hash, or a request. */
  select(panelId: string): void;
  /** Folds the column, if the panel is the open one. Returns whether it was. */
  deselect(panelId: string): boolean;
  /**
   * Folds the column once the shell overlays it, or brings back the panel
   * that closed once there's room again. One the person closed stays closed.
   */
  setOverlaid(overlaid: boolean): void;
}

/** See {@link ScreenPanels.start}. */
export type PreferredPanel = string | null | undefined;

export function createScreenPanels(): ScreenPanels {
  const registered = shallowReactive(new Map<string, DetailsPanel>());
  const openPanelId = shallowRef<string | null>(null);
  const pageGeneration = shallowRef(0);
  const sections = shallowReactive(new Map<string, HTMLElement>());
  let controller: ScreenPanelsController | null = null;
  /** A panel to open once the column is mounted and lists it. */
  let pendingOpen: {panelId: string; withFocus: boolean} | null = null;
  /**
   * Whether the open panel was opened on purpose: by the person, the URL
   * hash, or a request, rather than by default.
   */
  let openedOnPurpose = false;
  /** The panel the shell closed for lack of room, to bring back with it. */
  let panelClosedByShell: string | null = null;

  const panels = computed(() =>
    [...registered.values()].sort(
      (first, second) => (first.order ?? 0) - (second.order ?? 0)
    )
  );

  function isVisible(panelId: string | null): boolean {
    return panels.value.some((panel) => panel.id === panelId);
  }

  /**
   * The panel a column opens on: the page's first, so a panel that stays from
   * page to page doesn't take over the page's details, or that one when the
   * page has none.
   */
  function defaultPanelId(): string | null {
    return (
      panels.value.find((panel) => !panel.persistent)?.id ??
      panels.value[0]?.id ??
      null
    );
  }

  /** The panel to open on when a page's panels arrive. */
  function initialPanelId(preferred: PreferredPanel): string | null {
    // One that stays from page to page, opened on purpose, stays open.
    if (
      openedOnPurpose &&
      panels.value.find((panel) => panel.id === openPanelId.value)?.persistent
    ) {
      return openPanelId.value;
    }

    openedOnPurpose = false;

    if (preferred === null) {
      return null;
    }

    return preferred !== undefined && isVisible(preferred)
      ? preferred
      : defaultPanelId();
  }

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
    panels,
    openPanelId: readonly(openPanelId),
    pageGeneration,
    sections,

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

    setSection(panelId, element) {
      if (element) {
        sections.set(panelId, element);
      } else if (sections.get(panelId)) {
        sections.delete(panelId);
      }
    },

    start(preferred) {
      openedOnPurpose = false;
      panelClosedByShell = null;
      openPanelId.value = initialPanelId(preferred);
    },

    startPage(preferred, overlaid) {
      openPanelId.value = overlaid ? null : initialPanelId(preferred);
    },

    reconcile(preferred) {
      if (openPanelId.value && !isVisible(openPanelId.value)) {
        openPanelId.value = panels.value.some((panel) => !panel.persistent)
          ? defaultPanelId()
          : null;
      } else if (openPanelId.value !== null && !openedOnPurpose) {
        openPanelId.value = initialPanelId(preferred);
      }
    },

    select(panelId) {
      openPanelId.value = panelId;
      openedOnPurpose = true;
      panelClosedByShell = null;
    },

    deselect(panelId) {
      if (openPanelId.value !== panelId) {
        return false;
      }

      openPanelId.value = null;
      panelClosedByShell = null;

      return true;
    },

    setOverlaid(overlaid) {
      if (overlaid) {
        if (openPanelId.value !== null) {
          panelClosedByShell = openPanelId.value;
          openPanelId.value = null;
        }

        return;
      }

      if (panelClosedByShell !== null && openPanelId.value === null) {
        openPanelId.value = isVisible(panelClosedByShell)
          ? panelClosedByShell
          : defaultPanelId();
      }

      panelClosedByShell = null;
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
