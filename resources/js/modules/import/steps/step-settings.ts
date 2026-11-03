/**
 * Building one import step's settings form.
 *
 * A step is unsaved client state the steps screen holds, with no URL to fetch. The
 * server is POSTed the draft step and returns only a UI payload for it, so nothing
 * reaches the database until the plan itself is saved.
 */
import {actionClient} from '@craftcms/ui';
import {
  sourcePreview,
  stepSettings,
} from '@actions/Import/ImportPlansController';
import type {UiPayload} from '@/modules/ui/types';
import type {ImportStep} from '@/modules/import/mapping/types';

export type StepUiPayload = Omit<
  CraftCms.Cms.Import.Data.StepUiPayload,
  'ui'
> & {
  ui: UiPayload;
};

/** Fetches the UI for a draft step, and whether that step can be mapped yet. */
export async function fetchStepUi(step: ImportStep): Promise<StepUiPayload> {
  const {data} = await actionClient.post(stepSettings().url, {step});

  if (!data.ui) {
    throw new Error('The import step did not return a UI payload.');
  }

  return {
    ui: data.ui as UiPayload,
    canMap: Boolean(data.canMap),
    sourceError: data.sourceError ?? null,
  };
}

/** A source's first rows, keyed by the paths its columns use. */
export interface SourcePreview {
  columns: string[];
  rows: Record<string, string>[];
  total: number;
  error: string | null;
}

/** Reads a draft step's data source for its first rows. */
export async function fetchSourcePreview(
  step: ImportStep
): Promise<SourcePreview> {
  const {data} = await actionClient.post(sourcePreview().url, {step});

  return data as SourcePreview;
}
