/**
 * Opening one import step's settings in a slideout panel.
 *
 * Opened with `openSlideoutWith()` rather than `openSlideout()`: a step is unsaved
 * client state the edit screen holds, with no URL to fetch. The server is POSTed the
 * draft step and returns only a Form payload for it; the panel edits a copy and hands
 * it back on Done, so cancelling leaves the screen untouched and nothing reaches the
 * database until the import itself is saved.
 */
import {actionClient} from '@craftcms/ui';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import type {FormPayload} from '@/modules/forms/types';
import type {StepPayload} from '@/modules/import/mapping/types';
import {cloneStep} from './clone';

export interface StepUrls {
  settingsUrl: string;
  mappingUrl: string;
  nestedColsUrl: string;
}

export interface StepFormResponse {
  form: FormPayload;
  /**
   * Whether the step has settled enough to be mapped. An element importer has no
   * destination columns until its field layout resolves, which only the server can tell.
   */
  canMap: boolean;
}

export interface StepSlideoutContext {
  step: StepPayload;
  payload: FormPayload;
  canMap: boolean;
  urls: StepUrls;
  editable: boolean;
  apply(step: StepPayload): void;
}

export interface OpenStepSlideoutOptions {
  step: StepPayload;
  urls: StepUrls;
  editable: boolean;
  opener: HTMLElement | null;
  apply(step: StepPayload): void;
}

// Callbacks can't ride in `ScreenPageProps`, so the panel is handed a key instead and
// claims its context on setup.
const contexts = new Map<string, StepSlideoutContext>();
let nextContextId = 0;

export function takeStepSlideoutContext(
  contextId: string
): StepSlideoutContext {
  const context = contexts.get(contextId);

  if (!context) {
    throw new Error(`Unknown import step context: ${contextId}`);
  }

  contexts.delete(contextId);

  return context;
}

/** Fetches the form for a draft step, and whether that step can be mapped yet. */
export async function fetchStepForm(
  settingsUrl: string,
  step: StepPayload
): Promise<StepFormResponse> {
  const {data} = await actionClient.post(settingsUrl, {step});

  if (!data.form) {
    throw new Error('The import step did not return a Form payload.');
  }

  return {form: data.form as FormPayload, canMap: Boolean(data.canMap)};
}

/**
 * Returns false when the panel was not opened — the user declined to discard unsaved
 * changes in a panel this one would have replaced.
 */
export async function openStepSlideout(
  options: OpenStepSlideoutOptions,
  title: string
): Promise<boolean> {
  const step = cloneStep(options.step);
  const {form, canMap} = await fetchStepForm(options.urls.settingsUrl, step);

  const [{openSlideoutWith}, {default: StepSlideout}] = await Promise.all([
    import('@/common/slideouts'),
    import('./StepSlideout.vue'),
  ]);

  const contextId = `import-step-${++nextContextId}`;
  contexts.set(contextId, {
    step,
    payload: form,
    canMap,
    urls: options.urls,
    editable: options.editable,
    apply: options.apply,
  });

  // SAFETY: The slideout host renders this imported Vue SFC exactly like its Inertia
  // page components; it does not require an Inertia page module.
  const panel = openSlideoutWith(
    StepSlideout as InertiaPageComponent,
    {contextId, title},
    {opener: options.opener}
  );

  if (!panel) {
    contexts.delete(contextId);

    return false;
  }

  return true;
}
