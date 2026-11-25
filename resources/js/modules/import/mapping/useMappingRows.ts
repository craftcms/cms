import {computed, shallowRef, toValue, type MaybeRefOrGetter} from 'vue';
import {isCompoundMappingColumn, isMappingColumn} from './paths';
import type {MappingColumn, MappingColumnEntry} from './types';

/** A run of columns, after a heading row when it's named, as a Matrix field's entry types are. */
export interface MappingTableGroup {
  label: string | null;
  cols: MappingColumnEntry[];
}

export type MappingRowData =
  | {kind: 'column'; id: string; col: MappingColumn}
  | {kind: 'heading'; id: string; label: string};

/**
 * The mapping table's rows, narrowed by a search of the columns' names and handles. A
 * heading row is kept only while one of its columns is.
 */
export function useMappingRows(groups: MaybeRefOrGetter<MappingTableGroup[]>) {
  const search = shallowRef('');

  function isShown(col: MappingColumn): boolean {
    const term = search.value.trim().toLowerCase();

    return (
      !term ||
      col.label.toLowerCase().includes(term) ||
      col.handle.toLowerCase().includes(term)
    );
  }

  function columnRow(col: MappingColumn, group: number): MappingRowData {
    return {kind: 'column', id: `${group}:${col.prefixedHandle}`, col};
  }

  function rowsOf(cols: MappingColumnEntry[], group: number): MappingRowData[] {
    return cols.flatMap((entry, index): MappingRowData[] => {
      if (isCompoundMappingColumn(entry)) {
        const subfields = entry.subfields.filter(isShown);

        if (!subfields.length) {
          return [];
        }

        return [
          ...(entry.heading
            ? [
                {
                  kind: 'heading' as const,
                  id: `heading-${group}-${index}`,
                  label: entry.heading,
                },
              ]
            : []),
          ...subfields.map((col) => columnRow(col, group)),
        ];
      }

      return isMappingColumn(entry) && isShown(entry)
        ? [columnRow(entry, group)]
        : [];
    });
  }

  const rows = computed(() =>
    toValue(groups).flatMap((group, index): MappingRowData[] => {
      const groupRows = rowsOf(group.cols, index);

      return groupRows.length && group.label !== null
        ? [
            {kind: 'heading', id: `group-${index}`, label: group.label},
            ...groupRows,
          ]
        : groupRows;
    })
  );

  return {search, rows};
}
