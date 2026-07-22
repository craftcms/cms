import {DragDrop} from '@craftcms/garnish';
import {t} from '@craftcms/ui';
import {onBeforeUnmount, onMounted, ref} from 'vue';
import {
  type AssetMoveConflict,
  type ConflictResolution,
  moveAssets,
} from './assetMover';

export interface UseAssetMoveDragOptions {
  /** Whether asset-move drag should be wired up (asset index only). */
  enabled: () => boolean;
  /** Ids of the currently selected asset rows (numeric; excludes folders). */
  selectedAssetIds: () => number[];
  /** Ensure the given row id is part of the selection (force-select on grab). */
  ensureSelected: (rowId: string) => void;
  /** Refresh the listing + counts after a successful move. */
  onMoved: () => void;
}

/** A pending filename-conflict prompt awaiting the user's choice. */
export interface AssetMoveConflictPrompt {
  conflict: AssetMoveConflict;
  resolve: (choice: ConflictResolution) => void;
}

/**
 * Drag-and-drop moving of assets into folders, ported from Craft 5's
 * `AssetIndex.itemDrag` onto the modern `@craftcms/garnish` `DragDrop`.
 *
 * Selected asset rows are draggable; folders (sidebar sources and folder rows in
 * the listing, marked `[data-folder-drop-target][data-can-move-to]`) are drop
 * targets. Dropping issues `assets/move-asset` for each selected asset, with a
 * keep-both / replace / cancel prompt on filename conflicts.
 *
 * The conflict prompt is surfaced as reactive state (`conflictPrompt`) so the
 * page can render a dialog; `resolveConflictChoice` settles it.
 */
export function useAssetMoveDrag(options: UseAssetMoveDragOptions) {
  let dragDrop: DragDrop | null = null;

  const conflictPrompt = ref<AssetMoveConflictPrompt | null>(null);

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

  function assetRows(): HTMLElement[] {
    return Array.from(
      document.querySelectorAll<HTMLElement>(
        '.element-index__body [data-movable-asset]'
      )
    );
  }

  function selectedElements(): HTMLElement[] {
    return options
      .selectedAssetIds()
      .map((id) =>
        document.querySelector<HTMLElement>(
          `.element-index__body [data-movable-asset][data-id="${id}"]`
        )
      )
      .filter((el): el is HTMLElement => el !== null);
  }

  function dropTargets(): HTMLElement[] {
    return Array.from(
      document.querySelectorAll<HTMLElement>(
        '[data-folder-drop-target][data-can-move-to]'
      )
    );
  }

  async function performMove(targetFolderId: number) {
    const assetIds = options.selectedAssetIds();
    if (!assetIds.length) {
      return;
    }

    try {
      const {moved} = await moveAssets(
        assetIds,
        targetFolderId,
        resolveConflict
      );
      if (moved > 0) {
        Craft.cp?.displayNotification?.(
          'notice',
          t('{num, plural, =1{Item} other{Items}} moved.', {num: moved})
        );
        options.onMoved();
      }
    } catch (e) {
      Craft.cp?.displayError?.(t('Couldn’t move the selected items.'));
      throw e;
    }
  }

  function refreshItems() {
    if (!dragDrop) {
      return;
    }
    dragDrop.removeAllItems();
    const rows = assetRows();
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
      // Force the grabbed row into the selection, then drag everything selected.
      filter: () => {
        const grabbed = dragDrop?.$targetItem;
        if (grabbed?.dataset.id) {
          options.ensureSelected(grabbed.dataset.id);
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

        if (!Number.isFinite(folderId)) {
          dragDrop?.returnHelpersToDraggees();
          return;
        }

        dragDrop?.fadeOutHelpers();
        void performMove(folderId);
      },
    });

    refreshItems();
    observeListing();
  }

  onMounted(() => {
    if (options.enabled()) {
      setup();
    }
  });

  onBeforeUnmount(() => {
    unbindDragKeys();
    observer?.disconnect();
    observer = null;
    dragDrop?.destroy();
    dragDrop = null;
  });

  return {conflictPrompt, resolveConflictChoice};
}
