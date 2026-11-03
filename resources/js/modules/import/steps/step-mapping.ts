/**
 * Loading one step's field mapping.
 *
 * Same shape as `nested-mapping.ts`: the server is POSTed the draft step and returns
 * only the structure — which destination columns exist and what the source file's
 * headings are — along with the step's mapping trees and its guesses at filling them.
 */
import {actionClient} from '@craftcms/ui';
import {stepMapping} from '@actions/Import/ImportPlansController';
import {
  applySuggestions,
  cloneValues,
  toObjectTree,
} from '@/modules/import/mapping/paths';
import type {
  MappingColumnEntry,
  MappingValues,
  SourceColumn,
  ImportStep,
  SuggestedMap,
} from '@/modules/import/mapping/types';
import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';

/** Everything a step's mapping editor starts from. */
export interface StepMappingData {
  destinationCols: MappingColumnEntry[];
  sourceDataCols: SourceColumn[];
  templateTextExpanderTriggers: TextExpanderTriggers;
  values: MappingValues;
  suggestedMap: SuggestedMap;
  /** What the step imports, in the plural and lowercase, e.g. “entries”. */
  targetName: string | null;
}

export type StepMappingPayload = CraftCms.Cms.Import.Data.StepMappingPayload;

/** Thrown when a step can't be mapped yet, carrying the step attribute that's in the way, if any. */
export class StepMappingUnavailableError extends Error {
  constructor(
    message: string,
    public readonly attribute: string | null = null
  ) {
    super(message);
    this.name = 'StepMappingUnavailableError';
  }
}

/** Asks the server for a draft step's mapping structure. */
export async function fetchStepMapping(
  step: ImportStep
): Promise<StepMappingPayload> {
  const {data} = await actionClient.post(stepMapping().url, {step});

  return data as StepMappingPayload;
}

/**
 * Loads what a step's mapping editor needs, with the server's suggestions filled in
 * wherever nothing's mapped yet. Throws with the server's reason when the step can't be
 * mapped yet — e.g. nothing to import into is chosen, or its data source can't be read.
 */
export async function loadStepMapping(
  step: ImportStep
): Promise<StepMappingData> {
  const data = await fetchStepMapping(step);

  if (!data.available) {
    throw new StepMappingUnavailableError(
      data.message ?? '',
      data.attribute ?? null
    );
  }

  const values = cloneValues(
    data.values ?? {
      map: {},
      matchCriteria: {},
      clearableItems: {},
      keepMissingNestedElements: {},
      fieldSettings: {},
      templates: {},
    }
  );
  const suggestedMap: SuggestedMap = {};
  applySuggestions(
    values.map,
    toObjectTree(data.suggestions ?? {}),
    suggestedMap
  );

  return {
    destinationCols: data.destinationCols ?? [],
    sourceDataCols: data.sourceDataCols ?? [],
    templateTextExpanderTriggers: (data.templateTextExpanderTriggers ??
      []) as TextExpanderTriggers,
    values,
    suggestedMap,
    targetName: data.targetName ?? null,
  };
}
