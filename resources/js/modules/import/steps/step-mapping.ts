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
  createContextRegistry,
  openContextSlideout,
} from '@/modules/import/context-slideout';
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

const registry = createContextRegistry<StepMappingContext>(
  'import-step-mapping'
);

export function takeStepMappingContext(contextId: string): StepMappingContext {
  return registry.take(contextId);
}

export interface StepMappingStructure {
  available: boolean;
  message?: string;
  /** The step attribute an unavailable step's message is about, e.g. `source`. */
  attribute?: string;
  destinationCols?: MappingColEntry[];
  sourceDataCols?: SourceDataCol[];
  values?: MappingValues;
  suggestions?: SuggestedMap;
}

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
  mappingUrl: string,
  step: StepPayload
): Promise<StepMappingStructure> {
  const {data} = await actionClient.post(mappingUrl, {step});

  return data as StepMappingStructure;
}

/**
 * Returns false when the user declined to discard unsaved changes in a panel this one
 * would have replaced. Throws with the server's reason when the step can't be mapped
 * yet — e.g. nothing to import into is chosen, or its data source can't be read.
 */
export async function openStepMapping(
  options: OpenStepMappingOptions,
  title: string
): Promise<boolean> {
  const data = await fetchStepMapping(options.urls.mappingUrl, options.step);

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
    }
  );
  const suggestedMap: SuggestedMap = {};
  applySuggestions(
    values.map,
    toObjectTree(data.suggestions ?? {}),
    suggestedMap
  );

  return openContextSlideout(
    registry,
    () =>
      import('./StepMapping.vue').then(
        (m) => m.default as InertiaPageComponent
      ),
    {
      destinationCols: data.destinationCols ?? [],
      sourceDataCols: data.sourceDataCols ?? [],
      values,
      suggestedMap,
      editable: options.editable,
      step: options.step,
      urls: options.urls,
      apply: options.apply,
    },
    title,
    options.opener
  );
}
