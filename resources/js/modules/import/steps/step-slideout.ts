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
import {
  stepSettings,
  validateStep as validateStepAction,
} from '@actions/Import/ImportPlansController';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import {
  createContextRegistry,
  openContextSlideout,
} from '@/modules/import/context-slideout';
import {cloneStep} from '@/modules/import/mapping/paths';
import type {FormPayload} from '@/modules/forms/types';
import type {ImportStep} from '@/modules/import/mapping/types';

export type StepFormPayload = Omit<
  CraftCms.Cms.Import.Data.StepFormPayload,
  'form'
> & {
  form: FormPayload;
};

export interface StepSlideoutContext {
  step: ImportStep;
  payload: FormPayload;
  canMap: boolean;
  sourceError: string | null;
  editable: boolean;
  apply(step: ImportStep): void;
}

export interface OpenStepSlideoutOptions {
  step: ImportStep;
  editable: boolean;
  opener: HTMLElement | null;
  apply(step: ImportStep): void;
}

const registry = createContextRegistry<StepSlideoutContext>('import-step');

export function takeStepSlideoutContext(
  contextId: string
): StepSlideoutContext {
  return registry.take(contextId);
}

/** Fetches the form for a draft step, and whether that step can be mapped yet. */
export async function fetchStepForm(
  step: ImportStep
): Promise<StepFormPayload> {
  const {data} = await actionClient.post(stepSettings().url, {step});

  if (!data.form) {
    throw new Error('The import step did not return a Form payload.');
  }

  return {
    form: data.form as FormPayload,
    canMap: Boolean(data.canMap),
    sourceError: data.sourceError ?? null,
  };
}

/**
 * Validates a draft step against its importer type's rules. Rejects with the axios error
 * (carrying `response.data.errors`) when the step is invalid.
 */
export async function validateStep(step: ImportStep): Promise<void> {
  await actionClient.post(validateStepAction().url, {step});
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
  const {form, canMap, sourceError} = await fetchStepForm(step);

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
      sourceError,
      editable: options.editable,
      apply: options.apply,
    },
    title,
    options.opener
  );
}
