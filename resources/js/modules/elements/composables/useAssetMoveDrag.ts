import {DragDrop} from '@craftcms/garnish';
import {t} from '@craftcms/ui';
import {onBeforeUnmount, onMounted, ref} from 'vue';
import {
    type AssetMoveConflict,
    type ConflictResolution,
    moveAssets,
} from './assetMover';
import {useElementIndexTable} from './useElementIndexTable';

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
export function useAssetMoveDrag() {
    const {table, onActionPerformed} = useElementIndexTable();

    let dragDrop: DragDrop | null = null;

    // The row selection as it was when the current drag began, captured before the
    // grab force-selects the dragged row. Restored on drop so a row that wasn't
    // already selected doesn't stay checked (a pre-selected group survives).
    let preDragSelection: Record<string, boolean> = {};

    const conflictPrompt = ref<AssetMoveConflictPrompt | null>(null);

    // Ids of the currently selected asset rows (numeric; folder rows excluded).
    function selectedAssetIds(): number[] {
        const selection = table.value?.getState().rowSelection ?? {};
        return Object.entries(selection)
            .filter(([id, selected]) => selected && !id.startsWith('folder:'))
            .map(([id]) => Number(id))
            .filter((id) => Number.isFinite(id));
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

    function assetRows(): HTMLElement[] {
        return Array.from(
            document.querySelectorAll<HTMLElement>(
                '.element-index__body [data-movable-asset]'
            )
        );
    }

    function selectedElements(): HTMLElement[] {
        return selectedAssetIds()
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

    async function performMove(targetFolderId: number, assetIds: number[]) {
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
                    t('{num, plural, =1{Item} other{Items}} moved.', {
                        num: moved,
                    })
                );
                onActionPerformed();
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
        const container =
            document.querySelector('.element-index') ?? document.body;
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
            // Snapshot the pre-grab selection first so onDragStop can undo a force
            // select of a row that wasn't already part of a selected group.
            filter: () => {
                preDragSelection = {...table.value?.getState().rowSelection};
                const grabbed = dragDrop?.$targetItem;
                if (grabbed?.dataset.id) {
                    const rowId = grabbed.dataset.id;
                    table.value?.setRowSelection((prev) => ({
                        ...prev,
                        [rowId]: true,
                    }));
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
                const assetIds = validTarget ? selectedAssetIds() : [];
                table.value?.setRowSelection(preDragSelection);

                if (!validTarget) {
                    dragDrop?.returnHelpersToDraggees();
                    return;
                }

                dragDrop?.fadeOutHelpers();
                void performMove(folderId, assetIds);
            },
        });

        refreshItems();
        observeListing();
    }

    onMounted(setup);

    onBeforeUnmount(() => {
        unbindDragKeys();
        observer?.disconnect();
        observer = null;
        dragDrop?.destroy();
        dragDrop = null;
    });

    return {conflictPrompt, resolveConflictChoice};
}
