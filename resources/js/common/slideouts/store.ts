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
  const id = `slideout-${++nextId}`;

  const panel = reactive<SlideoutInstance>({
    id,
    containerId: id,
    href,
    component: null,
    props: {},
    loading: true,
    error: null,
    opener:
      options.opener ??
      (document.activeElement instanceof HTMLElement
        ? document.activeElement
        : null),
  });

  panels.push(panel);

  await loadInto(panel);

  return panel;
}

export async function reloadSlideout(id: string): Promise<void> {
  const panel = findSlideout(id);

  if (panel) {
    await loadInto(panel);
  }
}

export function closeSlideout(id: string): void {
  const index = panels.findIndex((panel) => panel.id === id);

  if (index === -1) {
    return;
  }

  const [panel] = panels.splice(index, 1);

  // Focus has to go somewhere deliberate — the panel that owned it is gone.
  panel?.opener?.focus?.();
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
