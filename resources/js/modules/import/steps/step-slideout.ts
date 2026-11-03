/**
 * Opening one of the plan's steps in a slideout over its edit page.
 *
 * Opened with `openSlideoutWith()`: the step is the plan form's unsaved state, with no
 * URL to fetch. The panel edits a copy and hands it back when it's applied, so canceling
 * leaves the plan as it was, and a step can be added before the plan is ever saved.
 */
import {actionClient} from '@craftcms/ui';
import {validateStep as validateStepAction} from '@actions/Import/ImportPlansController';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import {cloneStep} from '@/modules/import/mapping/paths';
import type {ImportStep} from '@/modules/import/mapping/types';
import type {UiPayload} from '@/modules/ui/types';

export interface StepSlideoutContext {
  step: ImportStep;
  editable: boolean;
  /** The plan's errors about this step, from its last save. */
  errors: UiPayload['errors'];
  /** The step's own page, for the panel's open-in-a-new-tab link. Unset until it's saved. */
  editUrl: string | null;
  apply(step: ImportStep): void;
}

export interface OpenStepSlideoutOptions extends StepSlideoutContext {
  title: string;
  opener: HTMLElement | null;
}

const contexts = new Map<string, StepSlideoutContext>();
let nextContextId = 0;

export function takeStepSlideoutContext(id: string): StepSlideoutContext {
  const context = contexts.get(id);
  contexts.delete(id);

  if (!context) {
    throw new Error(`Unknown import step context: ${id}`);
  }

  return context;
}

/**
 * Validates a draft step against its importer type's rules. Rejects with the request's
 * error, carrying `response.data.errors`, when the step is invalid.
 */
export async function validateStep(step: ImportStep): Promise<void> {
  await actionClient.post(validateStepAction().url, {step});
}

/** Returns false when the panel didn't open, because the user kept a panel it would replace. */
export async function openStepSlideout({
  title,
  opener,
  ...context
}: OpenStepSlideoutOptions): Promise<boolean> {
  const [{openSlideoutWith}, {default: StepSlideout}] = await Promise.all([
    import('@/common/slideouts'),
    import('./StepSlideout.vue'),
  ]);

  const contextId = `import-step-${++nextContextId}`;
  contexts.set(contextId, {...context, step: cloneStep(context.step)});

  // SAFETY: The slideout host renders this imported Vue SFC exactly like its Inertia
  // page components; it does not require an Inertia page module.
  const panel = openSlideoutWith(
    StepSlideout as InertiaPageComponent,
    {contextId, title},
    {opener}
  );

  if (!panel) {
    contexts.delete(contextId);

    return false;
  }

  return true;
}
