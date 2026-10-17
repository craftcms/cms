import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import type {SlideoutInstance} from '@/common/slideouts';

export interface OpenColumnSettingsOptions {
  title: () => string;
  /** The column's current settings, serialized, so the panel can tell when they've changed. */
  settings: () => string;
  /** Receives the panel's body to teleport the settings into, or `null` once it's gone. */
  mount: (target: HTMLElement | null) => void;
  /** Keeps the changes. Closing the panel any other way discards them. */
  apply: () => void;
  opener: HTMLElement;
  onClosed: () => void;
}

export type ColumnSettingsContext = Pick<
  OpenColumnSettingsOptions,
  'title' | 'settings' | 'mount' | 'apply'
>;

const contexts = new Map<string, ColumnSettingsContext>();
let nextContextId = 0;

export function takeColumnSettingsContext(id: string): ColumnSettingsContext {
  const context = contexts.get(id);
  contexts.delete(id);
  if (!context) {
    throw new Error('Table column settings context was not found.');
  }
  return context;
}

/**
 * Opens a Table column's settings in a slideout.
 *
 * Opened with `openSlideoutWith()`: the settings are unsaved field settings,
 * with no screen to fetch. The panel only provides the chrome; the column
 * control teleports its own settings into it, so they keep the field settings
 * form's UI context.
 *
 * The Vue side is imported on demand, since UI controls also render in the
 * legacy bundle.
 */
export async function openColumnSettings({
  opener,
  onClosed,
  ...context
}: OpenColumnSettingsOptions): Promise<SlideoutInstance | null> {
  const [{openSlideoutWith}, {default: TableColumnSettings}] =
    await Promise.all([
      import('@/common/slideouts'),
      import('./TableColumnSettings.vue'),
    ]);

  const contextId = `table-column-settings-${++nextContextId}`;
  contexts.set(contextId, context);

  // SAFETY: The slideout host renders this imported Vue SFC exactly like its
  // Inertia page components; it does not require an Inertia page module.
  const panel = openSlideoutWith(
    TableColumnSettings as InertiaPageComponent,
    {contextId},
    {opener, onClosed}
  );

  if (!panel) {
    contexts.delete(contextId);
  }

  return panel;
}
