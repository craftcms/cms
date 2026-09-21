/**
 * Opening a container column's mapping in a slideout panel.
 *
 * Opened with `openSlideoutWith()` rather than `openSlideout()`: the mapping being
 * edited is unsaved client state the panel above already holds, with no URL to GET.
 * The server is POSTed the draft step and asked only for the container's *structure*
 * — which columns exist under it — and the panel edits a copy of the trees, handing
 * them back on Apply. Nothing needs to have been saved for this to work.
 *
 * That copy is the whole set of trees rather than just the container's branch, so a
 * nested column's absolute `prefixedHandleAsArray` addresses the same place in the
 * panel as it does on the page, and nesting works to any depth without rebasing paths.
 */
import {actionClient} from '@craftcms/ui';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import {
  createContextRegistry,
  openContextSlideout,
} from '@/modules/import/context-slideout';
import {applySuggestions, cloneValues, toObjectTree} from './paths';
import type {
  MappingCol,
  MappingGroup,
  MappingValues,
  SourceDataCol,
  StepPayload,
  SuggestedMap,
} from './types';

export interface NestedMappingContext {
  col: MappingCol;
  fieldName: string;
  groups: MappingGroup[];
  sourceDataCols: SourceDataCol[];
  values: MappingValues;
  /** Which of this panel's own `values.map` leaves hold a guessed value. */
  suggestedMap: SuggestedMap;
  editable: boolean;
  /** Carried through so a container inside the panel can open a panel of its own. */
  step: StepPayload;
  colsUrl: string;
  apply(values: MappingValues): void;
}

export interface OpenNestedMappingOptions {
  col: MappingCol;
  /** The draft step being mapped. Posted so the server can build its importer. */
  step: StepPayload;
  /** Endpoint returning the container's destination columns. */
  colsUrl: string;
  values: MappingValues;
  editable: boolean;
  opener: HTMLElement | null;
  apply(values: MappingValues): void;
}

const registry = createContextRegistry<NestedMappingContext>(
  'import-nested-mapping'
);

export function takeNestedMappingContext(
  contextId: string
): NestedMappingContext {
  return registry.take(contextId);
}

/**
 * Returns false when the panel was not opened — the user declined to discard unsaved
 * changes in a panel this one would have replaced.
 */
export async function openNestedMapping(
  options: OpenNestedMappingOptions
): Promise<boolean> {
  const {col} = options;

  const {data} = await actionClient.post(options.colsUrl, {
    step: options.step,
    fieldUid: col.fieldUid ?? '',
    fieldHandle: col.prefixedHandle,
    fieldIsProperty: col.isProperty ? 1 : 0,
  });

  const values = cloneValues(options.values);
  const suggestedMap: SuggestedMap = {};
  applySuggestions(
    values.map,
    toObjectTree(data.suggestions ?? {}),
    suggestedMap
  );

  return openContextSlideout(
    registry,
    () =>
      import('./NestedMapping.vue').then(
        (m) => m.default as InertiaPageComponent
      ),
    {
      col,
      fieldName: data.fieldName,
      groups: data.groups,
      sourceDataCols: data.sourceDataCols ?? [],
      values,
      suggestedMap,
      editable: options.editable,
      step: options.step,
      colsUrl: options.colsUrl,
      apply: options.apply,
    },
    data.title,
    options.opener
  );
}
