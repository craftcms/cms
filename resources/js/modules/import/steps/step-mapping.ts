/**
 * Opening one step's field mapping in a slideout panel, from inside that step's own
 * panel.
 *
 * Same shape as `nested-mapping.ts`: the server is POSTed the draft step and returns
 * only the structure — which destination columns exist and what the source file's
 * headings are — while the panel edits a copy of the step's mapping trees and hands
 * them back on Apply.
 */
import {actionClient} from '@craftcms/ui';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import {
  applySuggestions,
  cloneValues,
  toObjectTree,
} from '@/modules/import/mapping/paths';
import type {
  MappingColEntry,
  MappingValues,
  SourceDataCol,
  StepPayload,
  SuggestedMap,
} from '@/modules/import/mapping/types';
import type {StepUrls} from './step-slideout';

export interface StepMappingContext {
  destinationCols: MappingColEntry[];
  sourceDataCols: SourceDataCol[];
  values: MappingValues;
  suggestedMap: SuggestedMap;
  editable: boolean;
  step: StepPayload;
  urls: StepUrls;
  apply(values: MappingValues): void;
}

export interface OpenStepMappingOptions {
  step: StepPayload;
  urls: StepUrls;
  editable: boolean;
  opener: HTMLElement | null;
  apply(values: MappingValues): void;
}

const contexts = new Map<string, StepMappingContext>();
let nextContextId = 0;

export function takeStepMappingContext(contextId: string): StepMappingContext {
  const context = contexts.get(contextId);

  if (!context) {
    throw new Error(`Unknown import step mapping context: ${contextId}`);
  }

  contexts.delete(contextId);

  return context;
}

export interface StepMappingStructure {
  available: boolean;
  message?: string;
  destinationCols?: MappingColEntry[];
  sourceDataCols?: SourceDataCol[];
  values?: MappingValues;
  suggestions?: SuggestedMap;
}

/** Asks the server for a draft step's mapping structure. */
export async function fetchStepMapping(
  mappingUrl: string,
  step: StepPayload
): Promise<StepMappingStructure> {
  const {data} = await actionClient.post(mappingUrl, {step});

  return data as StepMappingStructure;
}

/**
 * Returns false when the panel was not opened — either the step isn't ready to be
 * mapped yet, or the user declined to discard unsaved changes in a panel this one
 * would have replaced.
 */
export async function openStepMapping(
  options: OpenStepMappingOptions,
  title: string
): Promise<boolean> {
  const data = await fetchStepMapping(options.urls.mappingUrl, options.step);

  if (!data.available) {
    return false;
  }

  const values = cloneValues(
    data.values ?? {
      map: {},
      matchCriteria: {},
      clearableItems: {},
      keepMissingNestedElements: {},
    }
  );
  const suggestedMap: SuggestedMap = {};
  applySuggestions(
    values.map,
    toObjectTree(data.suggestions ?? {}),
    suggestedMap
  );

  const [{openSlideoutWith}, {default: StepMapping}] = await Promise.all([
    import('@/common/slideouts'),
    import('./StepMapping.vue'),
  ]);

  const contextId = `import-step-mapping-${++nextContextId}`;
  contexts.set(contextId, {
    destinationCols: data.destinationCols ?? [],
    sourceDataCols: data.sourceDataCols ?? [],
    values,
    suggestedMap,
    editable: options.editable,
    step: options.step,
    urls: options.urls,
    apply: options.apply,
  });

  // SAFETY: The slideout host renders this imported Vue SFC exactly like its Inertia
  // page components; it does not require an Inertia page module.
  const panel = openSlideoutWith(
    StepMapping as InertiaPageComponent,
    {contextId, title},
    {opener: options.opener}
  );

  if (!panel) {
    contexts.delete(contextId);

    return false;
  }

  return true;
}
