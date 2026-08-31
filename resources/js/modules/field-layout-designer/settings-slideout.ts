import type {FormPayload, FormValues} from '@/modules/forms/types';
import {appendBodyHtml, appendHeadHtml} from '@craftcms/ui';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';

export interface OpenLayoutSettingsOptions {
  title: string;
  triggerElement?: HTMLElement | null;
  /** Identifies the component being edited, for the refresh request. */
  requestData: () => FormValues;
  /** Persists the settings. Rejects with the axios error on a failure. */
  apply: (values: FormValues) => Promise<void>;
}

interface LayoutSettingsResponse {
  form: FormPayload;
  headHtml?: string;
  bodyHtml?: string;
}

export interface LayoutSettingsContext {
  payload: FormPayload;
  requestData: () => FormValues;
  apply: (values: FormValues) => Promise<void>;
}

const contexts = new Map<string, LayoutSettingsContext>();
let nextContextId = 0;

export function takeLayoutSettingsContext(id: string): LayoutSettingsContext {
  const context = contexts.get(id);
  contexts.delete(id);
  if (!context) {
    throw new Error('Layout component settings context was not found.');
  }
  return context;
}

/**
 * Whether the Vue slideout stack is available on this page.
 *
 * `SlideoutHost` is only mounted by the Inertia CP shell, and the designer is
 * also reachable from legacy-stack screens via the `fieldLayoutDesigner()`
 * Twig function. The globals are registered when that shell boots, so their
 * presence is the documented signal for "this is an Inertia page".
 */
export function canUseVueSlideout(): boolean {
  return window.Craft?.openSlideout instanceof Function;
}

/**
 * Opens a layout component's settings in a Vue slideout panel.
 *
 * Opened with `openSlideoutWith()` rather than `openSlideout()`: the form is
 * built by POSTing the layout currently being edited, which is unsaved client
 * state with no URL to fetch. The panel owns its own Save and Cancel, so the
 * caller doesn't wire a footer or a submit handler.
 *
 * The Vue side is imported on demand. The designer is loaded by the legacy
 * bundle too, and a static import would drag the whole Inertia shell into
 * every page that renders a field layout.
 *
 * Returns false when the panel was not opened — the user declined to discard
 * unsaved changes in a panel this one would have replaced.
 */
export async function openLayoutComponentSettings(
  data: LayoutSettingsResponse,
  options: OpenLayoutSettingsOptions
): Promise<boolean> {
  const [{openSlideoutWith}, {default: LayoutComponentSettings}] =
    await Promise.all([
      import('@/common/slideouts'),
      import('./LayoutComponentSettings.vue'),
    ]);

  const contextId = `layout-settings-${++nextContextId}`;
  contexts.set(contextId, {
    payload: data.form,
    requestData: options.requestData,
    apply: options.apply,
  });

  // SAFETY: The slideout host renders this imported Vue SFC exactly like its
  // Inertia page components; it does not require an Inertia page module.
  const slideoutComponent = LayoutComponentSettings as InertiaPageComponent;
  const panel = openSlideoutWith(
    slideoutComponent,
    {
      contextId,
      title: options.title,
    },
    {
      opener: options.triggerElement ?? null,
    }
  );

  if (!panel) {
    contexts.delete(contextId);
    return false;
  }

  // Server-rendered controls in the form (condition builders, field selects)
  // register their own assets.
  if (data.headHtml) {
    await appendHeadHtml(data.headHtml);
  }
  if (data.bodyHtml) {
    await appendBodyHtml(data.bodyHtml);
  }

  return true;
}
