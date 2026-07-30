import {reactive, readonly, type DeepReadonly} from 'vue';
import {fetchSlideoutPage} from './request';
import type {OpenSlideoutOptions, SlideoutInstance} from './types';

/**
 * The open slideout stack, outermost first.
 *
 * A module singleton rather than a composable: slideouts outlive the component
 * that opened them, and `SlideoutHost` renders the stack from outside the page
 * tree so an Inertia navigation underneath can't unmount it.
 */
const panels = reactive<SlideoutInstance[]>([]);

let nextId = 0;

export function useSlideoutStack(): DeepReadonly<SlideoutInstance[]> {
  return readonly(panels) as DeepReadonly<SlideoutInstance[]>;
}

export function slideoutPanels(): SlideoutInstance[] {
  return panels;
}

export function findSlideout(id: string): SlideoutInstance | undefined {
  return panels.find((panel) => panel.id === id);
}

export async function openSlideout(
  href: string,
  options: OpenSlideoutOptions = {}
): Promise<SlideoutInstance> {
  const opener =
    options.opener ??
    (document.activeElement instanceof HTMLElement
      ? document.activeElement
      : null);

  // Opening a slideout replaces whatever is stacked above whatever opened it.
  // Double-clicking a second row on an index swaps the panel rather than
  // stacking a second one; opening from inside a panel nests below it.
  closeAbove(originPanel(opener));

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
    width: options.width ?? null,
  });

  panels.push(panel);

  await loadInto(panel);

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
 */
function closeAbove(panelId: string | null): void {
  const index = panelId
    ? panels.findIndex((panel) => panel.id === panelId)
    : -1;

  while (panels.length > index + 1) {
    // No focus restore: a new panel is about to take focus, and handing it
    // back to the old opener first makes it flicker.
    removePanel(panels[panels.length - 1]!.id, {restoreFocus: false});
  }
}

export async function reloadSlideout(id: string): Promise<void> {
  const panel = findSlideout(id);

  if (panel) {
    await loadInto(panel);
  }
}

export function closeSlideout(id: string): void {
  // Closing a panel takes anything stacked on top of it with it — those were
  // opened from inside it and have nowhere to sit once it's gone.
  closeAbove(id);
  removePanel(id);
}

function removePanel(id: string, {restoreFocus = true} = {}): void {
  const index = panels.findIndex((panel) => panel.id === id);

  if (index === -1) {
    return;
  }

  const [panel] = panels.splice(index, 1);

  if (restoreFocus) {
    // Focus has to go somewhere deliberate — the panel that owned it is gone.
    panel?.opener?.focus?.();
  }
}

export function closeAllSlideouts(): void {
  while (panels.length) {
    closeSlideout(panels[panels.length - 1]!.id);
  }
}

async function loadInto(panel: SlideoutInstance): Promise<void> {
  panel.loading = true;
  panel.error = null;

  try {
    const page = await fetchSlideoutPage(panel.href, panel.containerId);

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
