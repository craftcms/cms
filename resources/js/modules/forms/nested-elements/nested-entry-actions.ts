import {useEventListener} from '@vueuse/core';
import {toValue, type MaybeRefOrGetter, type Ref} from 'vue';
import type {ActionItem} from '@/common/types';
import type {
  BulkActionItem,
  ElementActionSelection,
  RunBulkAction,
} from '@/modules/elements/types/actions';
import {selectionAllows} from '@/modules/elements/types/actions';
import {
  DELETE_ACTION,
  DUPLICATE_ACTION,
  isCopyAction,
} from '@/modules/elements/index/element-action-identity';
import {withoutStraySeparators} from '@/modules/matrix/selection-menu';
import type {Selectable} from '@/common/composables/useSelectable';
import {copyElements} from '@/modules/elements/index/copy-elements';
import {runElementAction} from '@/modules/elements/index/element-actions';
import {
  nestedOwnerParams,
  type NestedEntry,
  type NestedEntriesManager,
} from './nested-entries';
import type {NestedEntryOperations} from './useNestedEntryOperations';

export function useNestedEntryActions(options: {
  entries: MaybeRefOrGetter<NestedEntry[]>;
  manager: MaybeRefOrGetter<NestedEntriesManager | null>;
  selection: Selectable<number>;
  operations: NestedEntryOperations;
  busy: MaybeRefOrGetter<boolean>;
}) {
  async function perform(
    item: BulkActionItem,
    run: RunBulkAction
  ): Promise<void> {
    const action = item.action;
    const manager = toValue(options.manager);
    if (!action || !manager || item.disabled || toValue(options.busy)) {
      return;
    }

    if (action.type === 'download') {
      await run();
      return;
    }
    if (action.type !== 'http') {
      return;
    }

    let performed = false;
    await options.operations.mutate(async (ownerId) => {
      performed = await run(nestedOwnerParams(manager, ownerId));
      return performed ? undefined : false;
    });

    if (performed) {
      options.selection.clear();
      if (item.key === DELETE_ACTION) {
        options.operations.focusCreateButton();
      }
    }
  }

  async function performRow(
    item: BulkActionItem,
    ids: number[],
    trigger: HTMLElement
  ): Promise<void> {
    const manager = toValue(options.manager);
    const entries = toValue(options.entries).filter((entry) =>
      ids.includes(entry.id)
    );
    if (
      !manager ||
      !item.action ||
      !ids.length ||
      entries.length !== ids.length ||
      !selectionAllows(item, entries) ||
      (item.key === DUPLICATE_ACTION && !options.operations.canAdd(ids.length))
    ) {
      return;
    }

    if (item.action.type === 'event' && isCopyAction(item)) {
      const serverElements = item.action.detail?.elements as
        | ElementActionSelection[]
        | undefined;
      copyElements(manager.elementType, ids, [
        ...(serverElements ?? []),
        ...entries,
      ]);
      return;
    }

    await perform(item, async (overrides) => {
      await runElementAction(
        item,
        {
          ...nestedOwnerParams(manager, manager.ownerId),
          elementType: manager.elementType,
          source: '__IMP__',
          context: 'embeddedIndex',
          elementIds: ids,
          ...overrides,
        },
        {trigger}
      );
      return true;
    });
  }

  return {perform, performRow};
}

export interface NestedEntryActionsOptions {
  entry: NestedEntry;
  selectedEntries: NestedEntry[];
  ids: number[];
  index: number;
  count: number;
  editable: boolean;
  busy: boolean;
  canPaste: boolean;
  canReorder: boolean;
  canAdd: (count: number) => boolean;
  pasteLabel?: string;
}

export interface NestedEntryActionEvent {
  action: string;
  elementId: number;
  item?: BulkActionItem;
  trigger: HTMLElement;
}

export interface NestedEntryActionHandlers {
  perform(item: BulkActionItem, ids: number[], trigger: HTMLElement): void;
  paste(beforeId: number): void;
  move(from: number, to: number): void;
}

export function nestedEntryActions({
  entry,
  selectedEntries,
  ids,
  index,
  count,
  editable,
  busy,
  canPaste,
  canReorder,
  canAdd,
  pasteLabel,
}: NestedEntryActionsOptions): ActionItem[] {
  return withoutStraySeparators(
    (entry.actionMenuItems ?? []).flatMap((item): ActionItem[] => {
      if (
        !('action' in item) ||
        item.action?.type !== 'event' ||
        item.action.name !== 'craft:nested-element-action'
      ) {
        return [item];
      }

      const action = item.action.detail?.action;
      if (typeof action !== 'string') {
        return [item];
      }

      if (
        (action === 'paste' && (!canPaste || !canReorder)) ||
        (action === 'move-forward' && (!canReorder || index === 0)) ||
        (action === 'move-backward' && (!canReorder || index === count - 1))
      ) {
        return [];
      }

      const bulkLabel = item.action.detail?.bulkLabel;
      const elementAction = item.action.detail?.item as
        | BulkActionItem
        | undefined;
      const isCopy = isCopyAction(elementAction);

      if (!editable && !isCopy) {
        return [];
      }

      return [
        {
          ...item,
          label:
            (ids.length > 1 && typeof bulkLabel === 'string'
              ? bulkLabel
              : undefined) ??
            (action === 'paste' && pasteLabel ? pasteLabel : item.label),
          disabled:
            item.disabled ||
            (!isCopy && busy) ||
            (action === 'element-action' &&
              (!elementAction ||
                !selectionAllows(elementAction, selectedEntries) ||
                (elementAction.key === DUPLICATE_ACTION &&
                  !canAdd(ids.length)))),
        },
      ];
    })
  );
}

export function nestedEntryActionEvent(
  event: Event,
  container: HTMLElement | null | undefined
): NestedEntryActionEvent | null {
  const detail = (event as CustomEvent<NestedEntryActionEvent>).detail;

  return detail?.trigger && container?.contains(detail.trigger) ? detail : null;
}

export function useNestedEntryActionEvents(
  container: Readonly<Ref<HTMLElement | null | undefined>>,
  options: {
    entries: MaybeRefOrGetter<NestedEntry[]>;
    actionIds: (entry: NestedEntry) => number[];
    editable: MaybeRefOrGetter<boolean>;
    busy: MaybeRefOrGetter<boolean>;
    handlers: NestedEntryActionHandlers;
    enabled?: MaybeRefOrGetter<boolean>;
  }
): void {
  useEventListener(window, 'craft:nested-element-action', (event: Event) => {
    if (!toValue(options.enabled ?? true)) {
      return;
    }

    const detail = nestedEntryActionEvent(event, container.value);
    const isCopy = isCopyAction(detail?.item);
    if (
      !detail ||
      (toValue(options.busy) && !isCopy) ||
      (!toValue(options.editable) && !isCopy)
    ) {
      return;
    }

    const entries = toValue(options.entries);
    const index = entries.findIndex((entry) => entry.id === detail.elementId);
    const entry = entries[index];
    if (!entry) {
      return;
    }

    const ids = options.actionIds(entry);
    switch (detail.action) {
      case 'element-action':
        if (detail.item) {
          options.handlers.perform(detail.item, ids, detail.trigger);
        }
        break;
      case 'paste':
        options.handlers.paste(entry.id);
        break;
      case 'move-forward':
      case 'move-backward':
        options.handlers.move(
          index,
          index + (detail.action === 'move-forward' ? -1 : 1)
        );
        break;
    }
  });
}
