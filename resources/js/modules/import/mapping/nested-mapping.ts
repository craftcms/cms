/**
 * Asking the server for a container column's own columns — a Matrix field's, say.
 *
 * The server is POSTed the draft step and returns only the container's *structure*:
 * which columns exist under it, grouped by the entry type (or other provider) they
 * belong to, plus its guesses at mapping them. The columns keep their absolute
 * `prefixedHandleAsArray`, so they read and write the same mapping trees as the
 * step's own columns, at any depth.
 */
import {actionClient} from '@craftcms/ui';
import {nestedMappingCols} from '@actions/Import/ImportPlansController';
import {toObjectTree} from './paths';
import type {
  ImportStep,
  MappingColumn,
  MappingColumnGroup,
  MappingValues,
} from './types';

export type NestedMappingPayload =
  CraftCms.Cms.Import.Data.NestedMappingPayload;

export interface NestedMappingData {
  groups: MappingColumnGroup[];
  /** The server's guesses for the container's columns, as a handle-keyed tree. */
  suggestions: Record<string, unknown>;
}

export async function fetchNestedMapping(
  step: ImportStep,
  values: MappingValues,
  col: MappingColumn
): Promise<NestedMappingData> {
  // PHP sends empty settings as `[]`
  const settings = Array.isArray(step.settings) ? {} : (step.settings ?? {});

  // the trees as they stand, so the server's guesses skip what's already been chosen
  const {data} = await actionClient.post<NestedMappingPayload>(
    nestedMappingCols().url,
    {
      step: {...step, settings: {...settings, ...values}},
      fieldUid: col.fieldUid ?? '',
      fieldHandle: col.prefixedHandle,
      fieldIsProperty: col.isProperty ? 1 : 0,
    }
  );

  return {
    groups: data.groups,
    suggestions: toObjectTree(data.suggestions ?? {}),
  };
}
