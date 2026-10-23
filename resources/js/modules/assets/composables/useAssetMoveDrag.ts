import {DragDrop} from '@craftcms/garnish';
import {createButton, isHttpError, t} from '@craftcms/ui';
import {onBeforeUnmount, onMounted, ref} from 'vue';
import {
  type AssetMoveConflict,
  type ConflictResolution,
  type FolderConflictResolution,
  type MovedAssetsResult,
  type MoveFoldersResult,
  getMoveInfo,
  isLargeMove,
  moveAssets,
  moveFolders,
} from '@/modules/assets/assetMover';
import type {ElementIndexOperations} from '@/modules/elements/index/types/model';

/** A pending filename-conflict prompt awaiting the user's choice. */
export interface AssetMoveConflictPrompt {
  conflict: AssetMoveConflict;
  resolve: (choice: ConflictResolution) => void;
}

export interface FolderMoveConflictPrompt {
  message: string;
  resolve: (choice: FolderConflictResolution) => void;
}

/** Where each moved item came from, so the move can be undone. */
interface MoveOrigins {
  /** Asset ID → the folder it was in. */
  assetFolderIds: Map<number, number>;
  /** Folder ID → the folder it was in. */
  folderParentIds: Map<number, number>;
}

interface UndoableMove {
  assets: Array<{assetId: number; folderId: number}>;
  folders: Array<{folderId: number; parentId: number}>;
}

interface Notice {
  close?: () => void;
}

/**
 * Drag-and-drop moving of assets into folders, ported from Craft 5's
 * `AssetIndex.itemDrag` onto the modern `@craftcms/garnish` `DragDrop`.
 *
 * Selected asset and folder rows are draggable; folders (sidebar sources,
 * breadcrumbs, and folder rows in the listing, marked
 * `[data-folder-drop-target][data-can-move-to]`) are drop targets. Folders move
 * first, followed by assets, with the appropriate conflict prompt for each.
 *
 * The conflict prompt is surfaced as reactive state (`conflictPrompt`) so the
 * page can render a dialog; `resolveConflictChoice` settles it.
 */
export function useAssetMoveDrag(index: ElementIndexOperations) {
  function onActionPerformed(): void {
    index.clearSelection();
    void index.refresh();
  }

  let dragDrop: DragDrop | null = null;

  // The row selection as it was when the current drag began, captured before the
  // grab force-selects the dragged row. Restored on drop so a row that wasn't
  // already selected doesn't stay checked (a pre-selected group survives).
  let restoreSelection = () => {};

  // The notice of the last move, while it still offers to undo it. A new move
  // closes it, so Undo only ever reverts the latest move.
  let undoNotice: Notice | null = null;

  const conflictPrompt = ref<AssetMoveConflictPrompt | null>(null);
  const folderConflictPrompt = ref<FolderMoveConflictPrompt | null>(null);

  function selectedMoveIds(): {assetIds: number[]; folderIds: number[]} {
    const selection = Object.fromEntries(
      index.view.selection.selectedIds.value.map((id) => [String(id), true])
    );
    const assetIds: number[] = [];
    const folderIds: number[] = [];

    for (const [id, selected] of Object.entries(selection)) {
      if (!selected) continue;

      const folderId = /^folder:(\d+)$/.exec(id)?.[1];
      if (folderId) {
        folderIds.push(Number(folderId));
      } else if (Number.isFinite(Number(id))) {
        assetIds.push(Number(id));
      }
    }

    return {assetIds, folderIds};
  }

  function resolveConflict(
    conflict: AssetMoveConflict
  ): Promise<ConflictResolution> {
    return new Promise((resolve) => {
      conflictPrompt.value = {conflict, resolve};
    });
  }

  function resolveConflictChoice(choice: ConflictResolution) {
    conflictPrompt.value?.resolve(choice);
    conflictPrompt.value = null;
  }

  function resolveFolderConflict(
    message: string
  ): Promise<FolderConflictResolution> {
    return new Promise((resolve) => {
      folderConflictPrompt.value = {message, resolve};
    });
  }

  function resolveFolderConflictChoice(choice: FolderConflictResolution) {
    folderConflictPrompt.value?.resolve(choice);
    folderConflictPrompt.value = null;
  }

  function movableRows(): HTMLElement[] {
    return Array.from(
      document.querySelectorAll<HTMLElement>(
        '.element-index__body [data-movable-item]'
      )
    );
  }

  function selectedElements(): HTMLElement[] {
    const selection = Object.fromEntries(
      index.view.selection.selectedIds.value.map((id) => [String(id), true])
    );

    return Object.entries(selection)
      .filter(([, selected]) => selected)
      .map(([id]) =>
        document.querySelector<HTMLElement>(
          `.element-index__body [data-movable-item][data-row-id="${id}"]`
        )
      )
      .filter((el): el is HTMLElement => el !== null);
  }

  function dropTargets(): HTMLElement[] {
    const {folderIds} = selectedMoveIds();
    const currentFolderId = Number(
      document.querySelector<HTMLElement>('[data-current-folder-id]')?.dataset
        .currentFolderId
    );
    const disabledFolderIds = Number.isFinite(currentFolderId)
      ? [...folderIds, currentFolderId]
      : folderIds;

    return Array.from(
      document.querySelectorAll<HTMLElement>(
        '[data-folder-drop-target][data-can-move-to]'
      )
    ).filter(
      (target) => !disabledFolderIds.includes(Number(target.dataset.folderId))
    );
  }

  function rowFolderId(id: string, key: string): number | null {
    const value = Number(index.findRow(id)?.[key]);
    return Number.isFinite(value) ? value : null;
  }

  /** Reads where each item is, before the move refreshes the listing. */
  function moveOrigins(assetIds: number[], folderIds: number[]): MoveOrigins {
    const assetFolderIds = new Map<number, number>();
    const folderParentIds = new Map<number, number>();

    for (const assetId of assetIds) {
      const folderId = rowFolderId(String(assetId), 'folderId');
      if (folderId !== null) assetFolderIds.set(assetId, folderId);
    }

    for (const folderId of folderIds) {
      const parentId = rowFolderId(`folder:${folderId}`, 'parentFolderId');
      if (parentId !== null) folderParentIds.set(folderId, parentId);
    }

    return {assetFolderIds, folderParentIds};
  }

  /**
   * What undoing a move takes, or null when it can't be undone: a resolved
   * conflict renamed or replaced something, or an item's origin is unknown.
   */
  function undoableMove(
    origins: MoveOrigins,
    folderResult: MoveFoldersResult,
    assetResult: MovedAssetsResult
  ): UndoableMove | null {
    if (
      folderResult.conflictedCount ||
      assetResult.conflictedCount ||
      folderResult.movedFolders.length !== folderResult.movedCount
    ) {
      return null;
    }

    const move: UndoableMove = {folders: [], assets: []};

    for (const {folderId, newFolderId} of folderResult.movedFolders) {
      const parentId = origins.folderParentIds.get(folderId);
      if (parentId === undefined) return null;
      move.folders.push({folderId: newFolderId, parentId});
    }

    for (const assetId of assetResult.movedAssetIds) {
      const folderId = origins.assetFolderIds.get(assetId);
      if (folderId === undefined) return null;
      move.assets.push({assetId, folderId});
    }

    return move;
  }

  function groupBy<T>(
    items: T[],
    key: (item: T) => number,
    id: (item: T) => number
  ): Map<number, number[]> {
    const groups = new Map<number, number[]>();
    for (const item of items) {
      const ids = groups.get(key(item)) ?? [];
      ids.push(id(item));
      groups.set(key(item), ids);
    }
    return groups;
  }

  function closeUndoNotice() {
    undoNotice?.close?.();
    undoNotice = null;
  }

  async function undoMove(move: UndoableMove) {
    let movedCount = 0;

    try {
      for (const [parentId, folderIds] of groupBy(
        move.folders,
        (folder) => folder.parentId,
        (folder) => folder.folderId
      )) {
        movedCount += (
          await moveFolders(folderIds, parentId, resolveFolderConflict)
        ).movedCount;
      }

      for (const [folderId, assetIds] of groupBy(
        move.assets,
        (asset) => asset.folderId,
        (asset) => asset.assetId
      )) {
        movedCount += (await moveAssets(assetIds, folderId, resolveConflict))
          .movedCount;
      }
    } catch (e) {
      Craft.cp?.displayError?.(t('Couldn’t move the selected items.'));
      throw e;
    } finally {
      if (movedCount > 0) {
        onActionPerformed();
      }
    }

    if (movedCount > 0) {
      Craft.cp?.displayNotification?.('notice', t('Move reverted.'));
    }
  }

  function showMovedNotice(movedCount: number, move: UndoableMove | null) {
    const message = t('{num, plural, =1{Item} other{Items}} moved.', {
      num: movedCount,
    });

    if (!move) {
      Craft.cp?.displayNotification?.('notice', message);
      return;
    }

    const undoButton = createButton({label: t('Undo')});
    const notice = (Craft.cp?.displayNotification?.('notice', message, {
      details: undoButton,
    }) ?? null) as Notice | null;
    undoNotice = notice;

    const dismiss = () => {
      notice?.close?.();
      if (undoNotice === notice) {
        undoNotice = null;
      }
    };

    undoButton.addEventListener('click', async () => {
      if (undoButton.loading) {
        return;
      }

      if (!confirm(t('Are you sure you want to undo the move?'))) {
        dismiss();
        return;
      }

      undoButton.loading = true;
      try {
        await undoMove(move);
      } finally {
        undoButton.loading = false;
        dismiss();
      }
    });
  }

  /**
   * Moves the items into a folder, and returns how many were moved. Moves
   * that are large or that needed a conflict resolved can't be undone.
   */
  async function performMove(
    targetFolderId: number,
    assetIds: number[],
    folderIds: number[]
  ): Promise<number> {
    if (!assetIds.length && !folderIds.length) {
      return 0;
    }

    closeUndoNotice();

    let largeMove: boolean;
    try {
      largeMove = isLargeMove(await getMoveInfo(folderIds, assetIds));
    } catch (e) {
      const message = isHttpError<{message?: string}>(e)
        ? e.response?.data?.message
        : undefined;
      Craft.cp?.displayError?.(message ?? t('A server error occurred.'));
      return 0;
    }

    if (
      largeMove &&
      !confirm(t('Are you sure you want to move the selected items?'))
    ) {
      return 0;
    }

    const origins = moveOrigins(assetIds, folderIds);

    try {
      const folderResult = await moveFolders(
        folderIds,
        targetFolderId,
        resolveFolderConflict
      );
      const assetResult = await moveAssets(
        assetIds,
        targetFolderId,
        resolveConflict
      );
      const movedCount = folderResult.movedCount + assetResult.movedCount;
      if (movedCount > 0) {
        showMovedNotice(
          movedCount,
          largeMove ? null : undoableMove(origins, folderResult, assetResult)
        );
        onActionPerformed();
      }
      return movedCount;
    } catch (e) {
      const message = isHttpError<{message?: string}>(e)
        ? e.response?.data?.message
        : undefined;
      Craft.cp?.displayError?.(
        message ?? t('Couldn’t move the selected items.')
      );
      throw e;
    }
  }

  function refreshItems() {
    if (!dragDrop) {
      return;
    }
    dragDrop.removeAllItems();
    const rows = movableRows();
    if (rows.length) {
      dragDrop.addItems(rows);
    }
  }

  // Escape-to-cancel. Garnish's drag is driven by pointer events with no native
  // cancel, so we end it by dispatching a `pointercancel` for the drag's own
  // pointer — that runs Garnish's normal teardown (→ onDragStop) without the
  // next pointermove re-starting the drag. `escaped` tells onDragStop to snap
  // the helpers back instead of moving. Only bound while a drag is in flight.
  let escaped = false;
  let activePointerId: number | null = null;

  function trackPointer(ev: PointerEvent) {
    activePointerId = ev.pointerId;
  }

  function onDragKeydown(ev: KeyboardEvent) {
    if (ev.key !== 'Escape') {
      return;
    }
    ev.preventDefault();
    ev.stopPropagation();
    escaped = true;
    document.dispatchEvent(
      new PointerEvent('pointercancel', {
        bubbles: true,
        pointerId: activePointerId ?? undefined,
      })
    );
  }

  function bindDragKeys() {
    escaped = false;
    activePointerId = null;
    document.addEventListener('keydown', onDragKeydown, true);
    document.addEventListener('pointermove', trackPointer, true);
  }

  function unbindDragKeys() {
    document.removeEventListener('keydown', onDragKeydown, true);
    document.removeEventListener('pointermove', trackPointer, true);
  }

  // The listing re-renders on its own schedule — Inertia partial reloads (after
  // a move), view-mode switches, folder navigation — and the new rows can paint
  // a tick after the reactive data settles. Rather than race Vue's flush, watch
  // the listing DOM directly and re-register whenever rows are added/removed.
  let observer: MutationObserver | null = null;
  let refreshScheduled = false;

  function scheduleRefresh() {
    if (refreshScheduled) {
      return;
    }
    refreshScheduled = true;
    requestAnimationFrame(() => {
      refreshScheduled = false;
      refreshItems();
    });
  }

  function observeListing() {
    const container = document.querySelector('.element-index') ?? document.body;
    observer = new MutationObserver(scheduleRefresh);
    observer.observe(container, {childList: true, subtree: true});
  }

  function setup() {
    dragDrop = new DragDrop({
      activeDropTargetClass: 'active-drop-target',
      minMouseDist: 10,
      moveHelperToCursor: true,
      helperOpacity: 0.85,
      ignoreHandleSelector:
        'a[href], input, textarea, button, select, .btn, [role="button"], [role="link"]',
      // Force the grabbed row into the selection, then drag everything selected.
      // Snapshot the pre-grab selection first so onDragStop can undo a force
      // select of a row that wasn't already part of a selected group.
      filter: () => {
        restoreSelection = index.captureSelection();
        const grabbed = dragDrop?.$targetItem;
        if (grabbed?.dataset.rowId) {
          const rowId = grabbed.dataset.rowId;
          const row = index.findRow(rowId);
          if (row) index.view.selection.selection.select(row.id, true);
        }
        return selectedElements();
      },
      dropTargets: () => dropTargets(),
      onDragStart: () => {
        document.body.classList.add('dragging');
        bindDragKeys();
      },
      onDragStop: () => {
        document.body.classList.remove('dragging');
        unbindDragKeys();

        // Escape (or no valid target) → snap the helpers back, move nothing.
        const target = escaped ? null : dragDrop?.$activeDropTarget;
        const folderId = target ? Number(target.dataset.folderId) : NaN;
        const validTarget = Number.isFinite(folderId);

        // Capture what to move while the grab's force-selection is still applied,
        // then restore the pre-drag selection so the dragged row returns to its
        // original checked state (a pre-selected group stays selected).
        const {assetIds, folderIds} = validTarget
          ? selectedMoveIds()
          : {assetIds: [], folderIds: []};
        restoreSelection();

        if (!validTarget) {
          dragDrop?.returnHelpersToDraggees();
          return;
        }

        // The draggees stay hidden under the fading helpers until the listing
        // refreshes; when nothing moves, there's no refresh to bring them back.
        const draggees = [...(dragDrop?.$draggee ?? [])];
        const showDraggees = () => {
          for (const draggee of draggees) {
            draggee.style.visibility = '';
          }
        };

        dragDrop?.fadeOutHelpers();
        void performMove(folderId, assetIds, folderIds)
          .then((movedCount) => {
            if (!movedCount) showDraggees();
          })
          .catch((e) => {
            showDraggees();
            throw e;
          });
      },
    });

    refreshItems();
    observeListing();
  }

  onMounted(setup);

  onBeforeUnmount(() => {
    closeUndoNotice();
    unbindDragKeys();
    observer?.disconnect();
    observer = null;
    dragDrop?.destroy();
    dragDrop = null;
  });

  return {
    conflictPrompt,
    resolveConflictChoice,
    folderConflictPrompt,
    resolveFolderConflictChoice,
    performMove,
  };
}
