import {reactive, shallowReadonly} from 'vue';
import {t} from '@craftcms/ui/utilities/translate';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import {fetchSlideoutPage, type SlideoutPage} from './request';
import type {
  OpenSlideoutOptions,
  SlideoutInstance,
  SlideoutSaveResult,
} from './types';
import type {ScreenPageProps} from '@/common/composables/screen';

/**
 * The open slideout stack, outermost first.
 *
 * A module singleton rather than a composable: slideouts outlive the component
 * that opened them, and `SlideoutHost` renders the stack from outside the page
 * tree so an Inertia navigation underneath can't unmount it.
 */
const panels = reactive<SlideoutInstance[]>([]);

let nextId = 0;

type SlideoutPageLoader = (
  href: string,
  containerId: string
) => Promise<SlideoutPage>;

let loadSlideoutPage: SlideoutPageLoader = fetchSlideoutPage;

/** Override the page loader for callers that own the transport lifecycle. */
export function setSlideoutPageLoader(
  loader: SlideoutPageLoader = fetchSlideoutPage
): void {
  loadSlideoutPage = loader;
}

export function useSlideoutStack(): readonly SlideoutInstance[] {
  return shallowReadonly(panels);
}

export function slideoutPanels(): SlideoutInstance[] {
  return panels;
}

export function findSlideout(id: string): SlideoutInstance | undefined {
  return panels.find((panel) => panel.id === id);
}

/**
 * Per-panel "does this have unsaved changes?" predicates, registered by
 * `SlideoutScreen` once it knows which kind of screen it's showing.
 *
 * The store owns the prompt rather than the shell because discarding isn't
 * only triggered by the close button: opening a slideout *replaces* whatever
 * is stacked above its opener, so a second double-click on an index would
 * otherwise throw away an edited panel with no warning.
 */
const dirtyChecks = new Map<string, () => boolean>();

export function setSlideoutDirtyCheck(
  id: string,
  check: (() => boolean) | null
): void {
  if (check) {
    dirtyChecks.set(id, check);
  } else {
    dirtyChecks.delete(id);
  }
}

function anyDirty(ids: string[]): boolean {
  return ids.some((id) => {
    try {
      return dirtyChecks.get(id)?.() ?? false;
    } catch {
      // A broken predicate must not wedge the panel shut.
      return false;
    }
  });
}

/** Returns false when the user chooses to keep editing. */
function confirmDiscard(ids: string[]): boolean {
  if (!anyDirty(ids)) {
    return true;
  }

  return window.confirm(
    t('Are you sure you want to close this screen? Any changes will be lost.')
  );
}

/**
 * Open a screen in a slideout.
 *
 * Resolves to `null` when the panel it would have replaced had unsaved changes
 * and the user chose to keep editing.
 */
export async function openSlideout(
  href: string,
  options: OpenSlideoutOptions = {}
): Promise<SlideoutInstance | null> {
  const opener =
    options.opener ??
    (document.activeElement instanceof HTMLElement
      ? document.activeElement
      : null);

  // Opening a slideout replaces whatever is stacked above whatever opened it.
  // Double-clicking a second row on an index swaps the panel rather than
  // stacking a second one; opening from inside a panel nests below it.
  if (!closeAbove(originPanel(opener))) {
    return null;
  }

  const id = `slideout-${++nextId}`;

  const panel = reactive<SlideoutInstance>({
    id,
    containerId: id,
    href,
    component: null,
    props: {},
    loading: true,
    error: null,
    opener,
    onSaved: options.onSaved ?? null,
    width: options.width ?? null,
  });

  panels.push(panel);

  await loadInto(panel);

  return panel;
}

/**
 * Open a locally-built component in a slideout, without fetching a screen.
 *
 * `openSlideout()` covers the normal case — a CP screen that lives at a URL.
 * Some panels have no URL to fetch: the field layout designer builds its
 * settings form by POSTing the layout being edited, which is unsaved client
 * state. Those supply the component and props themselves.
 *
 * The panel is otherwise identical, so stacking, the shade, focus handling,
 * Escape and dirty-checking all behave the same. `reload()` is a no-op: there
 * is nothing to re-fetch.
 */
export function openSlideoutWith(
  component: InertiaPageComponent,
  props: ScreenPageProps = {},
  options: OpenSlideoutOptions = {}
): SlideoutInstance | null {
  const opener =
    options.opener ??
    (document.activeElement instanceof HTMLElement
      ? document.activeElement
      : null);

  if (!closeAbove(originPanel(opener))) {
    return null;
  }

  const id = `slideout-${++nextId}`;

  const panel = reactive<SlideoutInstance>({
    id,
    containerId: id,
    href: '',
    component,
    props,
    loading: false,
    error: null,
    opener,
    onSaved: options.onSaved ?? null,
    width: options.width ?? null,
  });

  panels.push(panel);

  return panel;
}

/** The panel an element lives in, or `null` if it's on the base page. */
function originPanel(opener: HTMLElement | null): string | null {
  return (
    opener?.closest<HTMLElement>('[data-slideout-id]')?.dataset.slideoutId ??
    null
  );
}

/**
 * Close every panel stacked above `panelId`, or all of them when the opener
 * wasn't in a panel at all.
 *
 * Returns false when the user was asked about unsaved changes and declined, in
 * which case nothing is closed.
 */
function closeAbove(panelId: string | null, {force = false} = {}): boolean {
  const index = panelId
    ? panels.findIndex((panel) => panel.id === panelId)
    : -1;

  const doomed = panels.slice(index + 1).map((panel) => panel.id);

  if (!doomed.length) {
    return true;
  }

  if (!force && !confirmDiscard(doomed)) {
    return false;
  }

  while (panels.length > index + 1) {
    // No focus restore: a new panel is about to take focus, and handing it
    // back to the old opener first makes it flicker.
    removePanel(panels[panels.length - 1]!.id, {restoreFocus: false});
  }

  return true;
}

/**
 * Tell the opener its screen saved. See {@link SlideoutController.saved} for
 * what the return value means.
 */
export function notifySlideoutSaved(
  id: string,
  result: SlideoutSaveResult = {}
): boolean {
  const handler = findSlideout(id)?.onSaved;

  if (!handler) {
    return false;
  }

  handler(result);

  return true;
}

export async function reloadSlideout(id: string): Promise<void> {
  const panel = findSlideout(id);

  // Local panels (see openSlideoutWith) have nothing to re-fetch.
  if (panel && panel.href) {
    await loadInto(panel);
  }
}

/**
 * Close a panel.
 *
 * Prompts first if it — or anything nested inside it — has unsaved changes.
 * Pass `force` for programmatic closes that follow a successful save, where
 * the form may still look dirty until the page behind reloads.
 */
export function closeSlideout(id: string, {force = false} = {}): void {
  const index = panels.findIndex((panel) => panel.id === id);

  if (index === -1) {
    return;
  }

  // Closing a panel takes anything stacked on top of it with it — those were
  // opened from inside it and have nowhere to sit once it's gone. Ask about
  // all of them at once rather than prompting per panel.
  if (!force && !confirmDiscard(panels.slice(index).map((p) => p.id))) {
    return;
  }

  closeAbove(id, {force: true});
  removePanel(id);
}

function removePanel(id: string, {restoreFocus = true} = {}): void {
  const index = panels.findIndex((panel) => panel.id === id);

  if (index === -1) {
    return;
  }

  const [panel] = panels.splice(index, 1);
  dirtyChecks.delete(id);

  if (restoreFocus) {
    // Focus has to go somewhere deliberate — the panel that owned it is gone.
    panel?.opener?.focus?.();
  }
}

/**
 * Tear the whole stack down. Programmatic, so it doesn't prompt — user-driven
 * dismissal goes through {@link closeSlideout}.
 */
export function closeAllSlideouts(): void {
  while (panels.length) {
    closeSlideout(panels[panels.length - 1]!.id, {force: true});
  }
}

async function loadInto(panel: SlideoutInstance): Promise<void> {
  panel.loading = true;
  panel.error = null;

  try {
    const page = await loadSlideoutPage(panel.href, panel.containerId);

    panel.component = page.component;
    panel.props = page.props;
  } catch (error) {
    // A navigation fallback (stale assets, non-Inertia screen) already
    // redirected; leaving the panel in an error state would flash it first.
    panel.error =
      error instanceof Error ? error.message : 'Failed to load the screen.';
  } finally {
    panel.loading = false;
  }
}
