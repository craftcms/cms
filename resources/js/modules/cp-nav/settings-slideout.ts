import {actionClient} from '@craftcms/ui';
import {itemSettings} from '@actions/Settings/CpNavigationController';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import {openSlideoutWith} from '@/common/slideouts';
import type {UiPayload} from '@/modules/ui/types';
import type {NavNode} from './tree';

export interface NavItemSettingsContext {
  node: NavNode;
  readOnly: boolean;
  /** Keeps the changes. Closing the panel any other way discards them. */
  apply: (changes: Partial<NavNode>) => void;
}

const contexts = new Map<
  string,
  NavItemSettingsContext & {payload: UiPayload}
>();
let nextContextId = 0;

export function takeNavItemSettingsContext(
  id: string
): NavItemSettingsContext & {payload: UiPayload} {
  const context = contexts.get(id);
  contexts.delete(id);

  if (!context) {
    throw new Error('Nav item settings context was not found.');
  }

  return context;
}

/** What the server needs to know about a node to build or check its settings. */
export function postedNode(node: NavNode) {
  const {
    kind,
    source,
    type,
    label,
    defaultLabel,
    url,
    newWindow,
    icon,
    hidden,
    userCondition,
  } = node;

  return {
    kind,
    source,
    type,
    label,
    defaultLabel,
    url,
    newWindow,
    icon,
    hidden,
    userCondition,
  };
}

/**
 * Opens a nav item's settings in a slideout.
 *
 * Opened with `openSlideoutWith()`: the item is part of the builder's unsaved
 * tree, with no screen to fetch, so the form is built by posting the item.
 * Applying hands the changes back to the builder, which saves them along with
 * everything else.
 */
export async function openNavItemSettings(
  context: NavItemSettingsContext,
  opener: HTMLElement | null
): Promise<boolean> {
  const [{data}, {default: NavItemSettings}] = await Promise.all([
    actionClient.post<{ui: UiPayload}>(itemSettings.url(), {
      node: postedNode(context.node),
    }),
    import('./components/NavItemSettings.vue'),
  ]);
  const contextId = `nav-item-settings-${++nextContextId}`;
  contexts.set(contextId, {...context, payload: data.ui});

  // SAFETY: The slideout host renders this imported Vue SFC exactly like its
  // Inertia page components; it does not require an Inertia page module.
  const panel = openSlideoutWith(
    NavItemSettings as InertiaPageComponent,
    {contextId},
    // A no-op, so applying doesn't reload the page behind the panel.
    {opener, onSaved: () => {}}
  );

  if (!panel) {
    contexts.delete(contextId);
  }

  return panel !== null;
}
