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
import {stepMapping} from '@actions/Import/ImportPlansController';
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
import {openNestedMapping} from '@/modules/import/mapping/nested-mapping';
import type {
  MappingColumn,
  MappingColumnEntry,
  MappingValues,
  SourceColumn,
  ImportStep,
  SuggestedMap,
} from '@/modules/import/mapping/types';
import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';

export interface StepMappingContext {
  destinationCols: MappingColumnEntry[];
  sourceDataCols: SourceColumn[];
  templateTextExpanderTriggers: TextExpanderTriggers;
  values: MappingValues;
  suggestedMap: SuggestedMap;
  editable: boolean;
  step: ImportStep;
  /** The mapping's own page, linked from the slideout's header. Unset for a step yet to be saved. */
  editUrl?: string | null;
  /** The submit button's label: Apply in a slideout, Save on the mapping's own page. */
  submitLabel?: string;
  /** Hands the mapping back. On the mapping's own page this saves the step's plan. */
  apply(values: MappingValues): void | Promise<void>;
}

export interface OpenStepMappingOptions {
  step: ImportStep;
  editable: boolean;
  editUrl?: string | null;
  opener: HTMLElement | null;
  apply(values: MappingValues): void;
}

const registry = createContextRegistry<StepMappingContext>(
  'import-step-mapping'
);

export function takeStepMappingContext(contextId: string): StepMappingContext {
  return registry.take(contextId);
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

/** The trees a mapping starts from: the step's own, with the server's guesses filled in where nothing is mapped. */
export function initialMappingState(data: StepMappingPayload): {
  values: MappingValues;
  suggestedMap: SuggestedMap;
} {
  const values = cloneValues(
    data.values ?? {
      map: {},
      matchCriteria: {},
      clearableItems: {},
      defaultValues: {},
      keepMissingNestedElements: {},
      fieldSettings: {},
      templates: {},
      matchMode: null,
    }
  );
  const suggestedMap: SuggestedMap = {};
  applySuggestions(
    values.map,
    toObjectTree(data.suggestions ?? {}),
    suggestedMap
  );

  return {values, suggestedMap};
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
  const data = await fetchStepMapping(options.step);

  if (!data.available) {
    throw new StepMappingUnavailableError(
      data.message ?? '',
      data.attribute ?? null
    );
  }

  const {values, suggestedMap} = initialMappingState(data);

  return openContextSlideout(
    registry,
    () =>
      import('./StepMapping.vue').then(
        (m) => m.default as InertiaPageComponent
      ),
    {
      destinationCols: data.destinationCols ?? [],
      sourceDataCols: data.sourceDataCols ?? [],
      templateTextExpanderTriggers: (data.templateTextExpanderTriggers ??
        []) as TextExpanderTriggers,
      values,
      suggestedMap,
      editable: options.editable,
      step: options.step,
      editUrl: options.editUrl ?? null,
      apply: options.apply,
    },
    title,
    options.opener
  );
}

/**
 * Finds a container column among a step's destinations by its key in the map, which is
 * its layout element's uid, or its field's handle in a plan saved with handles.
 */
function findContainer(
  entries: MappingColumnEntry[],
  key: string
): MappingColumn | null {
  for (const entry of entries) {
    if ('subfields' in entry) {
      const found = findContainer(entry.subfields as MappingColumnEntry[], key);

      if (found) {
        return found;
      }
    } else if (
      entry.isContainer &&
      (entry.prefixedHandleAsArray.join('.') === key || entry.handle === key)
    ) {
      return entry;
    }
  }

  return null;
}

/**
 * Opens one of a step's container columns' mapping straight away, rather than from
 * inside the step's own mapping. Returns false, and throws, as `openStepMapping()` does.
 */
export async function openStepNestedMapping(
  options: OpenStepMappingOptions & {key: string}
): Promise<boolean> {
  const data = await fetchStepMapping(options.step);

  if (!data.available) {
    throw new StepMappingUnavailableError(
      data.message ?? '',
      data.attribute ?? null
    );
  }

  const col = findContainer(data.destinationCols ?? [], options.key);

  if (!col) {
    throw new StepMappingUnavailableError('');
  }

  return openNestedMapping({
    col,
    step: options.step,
    values: initialMappingState(data).values,
    editable: options.editable,
    opener: options.opener,
    apply: options.apply,
  });
}
