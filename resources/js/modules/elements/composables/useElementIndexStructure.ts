import type {RowSelectionState} from '@tanstack/vue-table';
import {computed, ref, type Ref} from 'vue';
import {
  createIndexVisitor,
  type ElementIndexRoute,
  type IndexRestore,
  type IndexVisitor,
} from '@/modules/elements/composables/useElementIndexVisits';
import type {ViewState} from '@/modules/elements/types/view-state';

interface ElementIndexStructureContext {
  structure: {id: number; editable: boolean; maxLevels: number | null} | null;
}

/** The parts of a row the structure helpers read. */
export interface StructureRow {
  id: string | number;
  level?: number;
  descendants?: number;
}

const levelOf = (row: StructureRow): number => row.level ?? 1;

/**
 * Indexes of the rows beneath `index`. Rows arrive in tree (`lft`) order, so a
 * row's descendants are the run of deeper rows that directly follows it.
 */
export function descendantIndexes(
  rows: ReadonlyArray<StructureRow>,
  index: number
): Array<number> {
  const level = levelOf(rows[index]!);
  const indexes: Array<number> = [];

  for (let i = index + 1; i < rows.length && levelOf(rows[i]!) > level; i++) {
    indexes.push(i);
  }

  return indexes;
}

/** Indexes of the rows above `index` in its branch, nearest first. */
export function ancestorIndexes(
  rows: ReadonlyArray<StructureRow>,
  index: number
): Array<number> {
  let level = levelOf(rows[index]!);
  const indexes: Array<number> = [];

  for (let i = index - 1; i >= 0 && level > 1; i--) {
    if (levelOf(rows[i]!) < level) {
      indexes.push(i);
      level = levelOf(rows[i]!);
    }
  }

  return indexes;
}

/**
 * Drops the descendants of collapsed rows. The server already excludes them,
 * so once a collapse request lands this is a no-op — it exists so a collapse
 * takes effect immediately rather than after the round trip.
 */
export function hideCollapsedDescendants<T extends StructureRow>(
  rows: ReadonlyArray<T>,
  isCollapsed: (id: string | number) => boolean
): Array<T> {
  const visible: Array<T> = [];
  let hiddenBelow: number | null = null;

  for (const row of rows) {
    const level = levelOf(row);

    if (hiddenBelow !== null && level > hiddenBelow) {
      continue;
    }

    hiddenBelow = isCollapsed(row.id) ? level : null;
    visible.push(row);
  }

  return visible;
}

/**
 * Applies a selection change to the tree: selecting a row selects everything
 * beneath it, and deselecting a row deselects everything beneath it plus its
 * ancestors — so a selected parent always means its whole branch is selected.
 *
 * Deselections apply first, so a row selected in the same change as its
 * branch is released still ends up selected.
 */
export function cascadeStructureSelection(
  previous: RowSelectionState,
  next: RowSelectionState,
  rows: ReadonlyArray<StructureRow>
): RowSelectionState {
  const indexById = new Map(rows.map((row, index) => [String(row.id), index]));
  const changed = [...new Set([...Object.keys(previous), ...Object.keys(next)])]
    .filter((id) => !!previous[id] !== !!next[id])
    .map((id) => ({index: indexById.get(id), selected: !!next[id]}))
    .filter(
      (change): change is {index: number; selected: boolean} =>
        change.index !== undefined
    );

  if (changed.length === 0) {
    return next;
  }

  const result: RowSelectionState = {...next};
  const idAt = (index: number) => String(rows[index]!.id);

  for (const {index} of changed.filter((change) => !change.selected)) {
    for (const i of [
      ...descendantIndexes(rows, index),
      ...ancestorIndexes(rows, index),
    ]) {
      delete result[idAt(i)];
    }
  }

  for (const {index} of changed.filter((change) => change.selected)) {
    for (const i of [index, ...descendantIndexes(rows, index)]) {
      result[idAt(i)] = true;
    }
  }

  return result;
}

/**
 * Selects the loaded descendants of every selected row. Expanding a selected
 * parent brings in children the selection has never seen, so they are pulled
 * in once they arrive.
 */
export function selectDescendantsOfSelected(
  selection: RowSelectionState,
  rows: ReadonlyArray<StructureRow>
): RowSelectionState {
  let result = selection;

  rows.forEach((row, index) => {
    if (!selection[String(row.id)]) {
      return;
    }

    for (const i of descendantIndexes(rows, index)) {
      const id = String(rows[i]!.id);
      if (!result[id]) {
        result = result === selection ? {...selection} : result;
        result[id] = true;
      }
    }
  });

  return result;
}

/** Removes the rows beneath `id` from the selection. */
export function deselectDescendants(
  selection: RowSelectionState,
  rows: ReadonlyArray<StructureRow>,
  id: string | number
): RowSelectionState {
  const index = rows.findIndex((row) => String(row.id) === String(id));
  const ids = index === -1 ? [] : descendantIndexes(rows, index);

  if (!ids.some((i) => selection[String(rows[i]!.id)])) {
    return selection;
  }

  const result = {...selection};
  ids.forEach((i) => delete result[String(rows[i]!.id)]);

  return result;
}

/**
 * Expand/collapse state for the structure view mode.
 *
 * Collapsing is server-driven: the index query excludes the descendants of
 * every collapsed element (see `ElementIndexes::buildQueryState()`), so a
 * collapsed subtree never counts toward pagination. Toggling re-requests the
 * list quietly — without the index-wide loading state — while the toggle
 * itself shows progress, and a collapse hides its rows immediately via
 * {@link hideCollapsedDescendants}.
 *
 * The collapsed set is persisted with the rest of the view state (local
 * storage), so a source reopens the way the user left it.
 */
export function useElementIndexStructure(
  props: ElementIndexStructureContext,
  viewState: Ref<ViewState>,
  route: ElementIndexRoute,
  /** Supplied by indexes that aren't a page — see {@link createIndexVisitor}. */
  indexVisitor?: IndexVisitor
) {
  const visitor = indexVisitor ?? createIndexVisitor(route);

  const isStructure = computed(() => viewState.value.mode === 'structure');

  /** The active structure, or `null` when the source isn't one. */
  const structure = computed(() => props.structure);

  const collapsedIds = computed(
    () => viewState.value.collapsedElementIds ?? []
  );

  /** The row whose expand/collapse request is in flight. */
  const pendingId = ref<string | number | null>(null);

  function isCollapsed(id: string | number): boolean {
    return collapsedIds.value.some(
      (collapsed) => String(collapsed) === String(id)
    );
  }

  function isPending(id: string | number): boolean {
    return pendingId.value !== null && String(pendingId.value) === String(id);
  }

  /** The rows to render, with collapsed branches hidden. */
  function visibleRows<T extends StructureRow>(
    rows: ReadonlyArray<T>
  ): Array<T> {
    return isStructure.value
      ? hideCollapsedDescendants(rows, isCollapsed)
      : [...rows];
  }

  function setCollapsedIds(
    ids: Array<number | string>,
    toggledId: string | number
  ): void {
    viewState.value.collapsedElementIds = ids;
    pendingId.value = toggledId;
    visitor.merge(
      {collapsedElementIds: ids},
      {
        only: ['data', 'pagination'],
        silent: true,
        onFinish: () => {
          if (isPending(toggledId)) {
            pendingId.value = null;
          }
        },
      }
    );
  }

  /** Collapses an expanded row, or expands a collapsed one. */
  function toggle(id: string | number): void {
    setCollapsedIds(
      isCollapsed(id)
        ? collapsedIds.value.filter(
            (collapsed) => String(collapsed) !== String(id)
          )
        : [...collapsedIds.value, id],
      id
    );
  }

  // The server renders the initial page with nothing collapsed — it can't see
  // the persisted view state — so a restored collapsed set needs re-requesting.
  // The page folds this into one mount-time restore visit alongside the
  // sort/column/view-mode restores (see `useElementIndexPage`).
  function restore(): IndexRestore | null {
    const params = new URLSearchParams(window.location.search);
    const hasInUrl = [...params.keys()].some(
      (key) =>
        key === 'collapsedElementIds' || key.startsWith('collapsedElementIds[')
    );

    if (hasInUrl || !isStructure.value || collapsedIds.value.length === 0) {
      return null;
    }

    return {
      params: {collapsedElementIds: collapsedIds.value},
      only: ['data', 'pagination'],
    };
  }

  return {
    isStructure,
    structure,
    collapsedIds,
    isCollapsed,
    isPending,
    visibleRows,
    toggle,
    restore,
  };
}
