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
import {
  createContextRegistry,
  openContextSlideout,
} from '@/modules/import/context-slideout';
import {cloneStep} from '@/modules/import/mapping/paths';
import type {FormPayload} from '@/modules/forms/types';
import type {StepPayload} from '@/modules/import/mapping/types';

export interface StepUrls {
  settingsUrl: string;
  validateUrl: string | null;
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

const registry = createContextRegistry<StepSlideoutContext>('import-step');

export function takeStepSlideoutContext(
  contextId: string
): StepSlideoutContext {
  return registry.take(contextId);
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
 * Validates a draft step against its importer type's rules. Rejects with the axios error
 * (carrying `response.data.errors`) when the step is invalid.
 */
export async function validateStep(
  validateUrl: string,
  step: StepPayload
): Promise<void> {
  await actionClient.post(validateUrl, {step});
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

  return openContextSlideout(
    registry,
    () =>
      import('./StepSlideout.vue').then(
        (m) => m.default as InertiaPageComponent
      ),
    {
      step,
      payload: form,
      canMap,
      urls: options.urls,
      editable: options.editable,
      apply: options.apply,
    },
    title,
    options.opener
  );
}
