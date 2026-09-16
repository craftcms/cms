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
  hasDescendants?: boolean;
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

/** The index of the row's parent, or -1 when it's a root (or off-page). */
export function parentIndex(
  rows: ReadonlyArray<StructureRow>,
  index: number
): number {
  return ancestorIndexes(rows, index)[0] ?? -1;
}

/** The index of the sibling directly before the row, or -1. */
export function previousSiblingIndex(
  rows: ReadonlyArray<StructureRow>,
  index: number
): number {
  const level = levelOf(rows[index]!);

  for (let i = index - 1; i >= 0; i--) {
    const rowLevel = levelOf(rows[i]!);

    if (rowLevel === level) {
      return i;
    }

    if (rowLevel < level) {
      return -1;
    }
  }

  return -1;
}

/** The index of the sibling directly after the row (past its subtree), or -1. */
export function nextSiblingIndex(
  rows: ReadonlyArray<StructureRow>,
  index: number
): number {
  const descendants = descendantIndexes(rows, index);
  const next = (descendants.at(-1) ?? index) + 1;

  return next < rows.length && levelOf(rows[next]!) === levelOf(rows[index]!)
    ? next
    : -1;
}

/**
 * A request to reposition a row: the keyboard/menu moves, or a drop relative
 * to another row (`before`/`after` it, as its first `child`, or `after` its
 * ancestor at `level` — the tree hitbox's "reparent").
 */
export type StructureMove =
  | {type: 'up' | 'down' | 'indent' | 'outdent'}
  | {type: 'before' | 'after' | 'child'; targetId: string | number}
  | {type: 'reparent'; targetId: string | number; level: number};

/** Where a moved row lands, in `structures/move-element` terms. */
export interface StructurePlacement {
  /** Move after this element (as its next sibling)… */
  prevId?: string | number;
  /** …or prepend to this element's children; neither means the root's start. */
  parentId?: string | number;
  /** The row's level once moved. */
  level: number;
  /** The new parent, if any — so a collapsed one can be opened. */
  newParentId?: string | number;
}

export interface ResolveStructureMoveOptions {
  /**
   * Whether the loaded rows begin at the top of the tree. Only then can a row
   * be placed at the start of the root level, since off-page roots may exist.
   */
  startsAtTop: boolean;
}

/**
 * Resolves a move into a placement, or `null` when it can't be made from the
 * rows at hand (no sibling to swap with, a neighbour on another page) or
 * wouldn't change anything.
 *
 * Positions are worked out against the rows with the moved branch removed,
 * so "before the row after me" and similar moves resolve correctly.
 */
export function resolveStructureMove(
  rows: ReadonlyArray<StructureRow>,
  sourceId: string | number,
  move: StructureMove,
  {startsAtTop}: ResolveStructureMoveOptions
): StructurePlacement | null {
  const sourceIndex = rows.findIndex(
    (row) => String(row.id) === String(sourceId)
  );

  if (sourceIndex === -1) {
    return null;
  }

  const branch = new Set([
    sourceIndex,
    ...descendantIndexes(rows, sourceIndex),
  ]);
  const rest = rows.filter((_, index) => !branch.has(index));
  const indexIn = (id: string | number) =>
    rest.findIndex((row) => String(row.id) === String(id));
  const idAt = (index: number) => rest[index]!.id;
  const level = levelOf(rows[sourceIndex]!);

  // Places the row at the start of `parent`'s children (or of the root).
  const firstUnder = (
    parent: number,
    newLevel: number
  ): StructurePlacement | null => {
    if (parent !== -1) {
      return {
        parentId: idAt(parent),
        newParentId: idAt(parent),
        level: newLevel,
      };
    }

    return startsAtTop && newLevel === 1 ? {level: 1} : null;
  };

  // Places the row directly before `target` in `rest`.
  const before = (target: number): StructurePlacement | null => {
    const targetLevel = levelOf(rest[target]!);
    const previous = previousSiblingIndex(rest, target);

    if (previous !== -1) {
      const parent = parentIndex(rest, target);
      return {
        prevId: idAt(previous),
        newParentId: parent === -1 ? undefined : idAt(parent),
        level: targetLevel,
      };
    }

    return firstUnder(parentIndex(rest, target), targetLevel);
  };

  // Places the row as the next sibling of `target` in `rest`.
  const after = (target: number): StructurePlacement => {
    const parent = parentIndex(rest, target);
    return {
      prevId: idAt(target),
      newParentId: parent === -1 ? undefined : idAt(parent),
      level: levelOf(rest[target]!),
    };
  };

  let placement: StructurePlacement | null;

  switch (move.type) {
    case 'up': {
      const previous = previousSiblingIndex(rows, sourceIndex);
      placement = previous === -1 ? null : before(indexIn(rows[previous]!.id));
      break;
    }
    case 'down': {
      const next = nextSiblingIndex(rows, sourceIndex);
      placement = next === -1 ? null : after(indexIn(rows[next]!.id));
      break;
    }
    case 'indent': {
      const previous = previousSiblingIndex(rows, sourceIndex);

      if (previous === -1) {
        placement = null;
        break;
      }

      // Become the last child of the sibling above: after its last loaded
      // child, or first (and possibly only) child when none are loaded.
      const newParent = indexIn(rows[previous]!.id);
      const lastChild = descendantIndexes(rest, newParent)
        .filter((index) => levelOf(rest[index]!) === level + 1)
        .at(-1);

      placement =
        lastChild === undefined
          ? firstUnder(newParent, level + 1)
          : {
              prevId: idAt(lastChild),
              newParentId: idAt(newParent),
              level: level + 1,
            };
      break;
    }
    case 'outdent': {
      const parent = parentIndex(rows, sourceIndex);
      placement = parent === -1 ? null : after(indexIn(rows[parent]!.id));
      break;
    }
    default: {
      const target = indexIn(move.targetId);

      if (target === -1) {
        placement = null;
      } else if (move.type === 'reparent') {
        const desiredLevel = move.level;
        const ancestor = [target, ...ancestorIndexes(rest, target)].find(
          (index) => levelOf(rest[index]!) === desiredLevel
        );
        placement = ancestor === undefined ? null : after(ancestor);
      } else if (move.type === 'before') {
        placement = before(target);
      } else if (move.type === 'after') {
        placement = after(target);
      } else {
        placement = firstUnder(target, levelOf(rest[target]!) + 1);
      }
    }
  }

  if (placement === null) {
    return null;
  }

  // A no-op: the row already sits exactly there.
  const currentPrevious = previousSiblingIndex(rows, sourceIndex);
  const currentParent = parentIndex(rows, sourceIndex);
  const unchanged =
    placement.prevId !== undefined
      ? currentPrevious !== -1 &&
        String(rows[currentPrevious]!.id) === String(placement.prevId)
      : currentPrevious === -1 &&
        (placement.parentId !== undefined
          ? currentParent !== -1 &&
            String(rows[currentParent]!.id) === String(placement.parentId)
          : currentParent === -1);

  return unchanged ? null : placement;
}

/**
 * How many levels the row's branch extends below it, from the loaded rows —
 * or `null` when the branch is collapsed and its depth is unknown.
 */
export function loadedBranchDepth(
  rows: ReadonlyArray<StructureRow>,
  id: string | number,
  isCollapsed: (id: string | number) => boolean
): number | null {
  const index = rows.findIndex((row) => String(row.id) === String(id));

  if (index === -1) {
    return null;
  }

  const row = rows[index]!;
  const descendants = descendantIndexes(rows, index);

  if (
    (isCollapsed(row.id) && !!row.hasDescendants) ||
    descendants.some(
      (i) => isCollapsed(rows[i]!.id) && !!rows[i]!.hasDescendants
    )
  ) {
    return null;
  }

  return Math.max(
    0,
    ...descendants.map((i) => levelOf(rows[i]!) - levelOf(row))
  );
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
