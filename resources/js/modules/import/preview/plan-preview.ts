/**
 * What running an import plan would do, step by step, without saving anything.
 * The server is posted the step as the form has it, so the preview follows
 * unsaved changes.
 */
import {actionClient, Color, t, type ColorValue} from '@craftcms/ui';
import {
  previewItem as previewItemAction,
  previewStep as previewStepAction,
} from '@actions/Import/ImportPlansController';
import type {
  CompareRow,
  CompareSide,
  CompareSidePayload,
} from '@/modules/elements/compareLayout';
import type {ImportStep} from '@/modules/import/mapping/types';

export type PreviewResult = 'new' | 'update' | 'unchanged' | 'skip' | 'problem';

export interface PreviewItem {
  /** The item's position in the step's data. */
  index: number;
  result: PreviewResult;
  title: string | null;
  /** The values it was matched on, by destination handle. */
  match: Record<string, string>;
  errors: string[];
}

export interface StepPreview {
  /** Why the step can't be previewed, if it can't. */
  error: string | null;
  /** How many items the step's data has, of which the first are previewed. */
  total?: number;
  matchColumns?: Array<{key: string; label: string}>;
  items?: PreviewItem[];
}

export interface ItemComparison {
  from: CompareSidePayload;
  to: CompareSidePayload;
  rows: CompareRow[];
  badged: Record<CompareSide, boolean>;
  /** The edit page of the element the item matches. */
  editUrl: string | null;
}

export interface ItemPreview {
  error: string | null;
  result?: PreviewResult;
  title?: string | null;
  errors?: string[];
  comparison?: ItemComparison | null;
}

export async function fetchStepPreview(step: ImportStep): Promise<StepPreview> {
  const {data} = await actionClient.post(previewStepAction().url, {step});

  return data as StepPreview;
}

export async function fetchItemPreview(
  step: ImportStep,
  index: number
): Promise<ItemPreview> {
  const {data} = await actionClient.post(previewItemAction().url, {
    step,
    index,
  });

  return data as ItemPreview;
}

export const resultLabels: Record<PreviewResult, string> = {
  new: t('New'),
  update: t('Update'),
  unchanged: t('No changes'),
  skip: t('Skip'),
  problem: t('Problem'),
};

export const resultFills: Record<PreviewResult, ColorValue> = {
  new: Color.Success,
  update: Color.Accent,
  unchanged: Color.Gray,
  skip: Color.Gray,
  problem: Color.Danger,
};
