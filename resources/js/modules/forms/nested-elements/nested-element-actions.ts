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
  nestedIndexParams,
  nestedOwnerParams,
  type NestedContentIndexData,
  type NestedElement,
  type NestedElementsManager,
} from './nested-elements';
import type {NestedElementOperations} from './useNestedElementOperations';

export function useNestedElementActions(options: {
  elements: MaybeRefOrGetter<NestedElement[]>;
  manager: MaybeRefOrGetter<NestedElementsManager | null>;
  initial?: MaybeRefOrGetter<NestedContentIndexData | undefined>;
  selection: Selectable<number>;
  operations: NestedElementOperations;
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
      performed = await run(
        nestedIndexParams(manager, ownerId, toValue(options.initial))
      );
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
    const elements = toValue(options.elements).filter((element) =>
      ids.includes(element.id)
    );
    if (
      !manager ||
      !item.action ||
      !ids.length ||
      elements.length !== ids.length ||
      !selectionAllows(item, elements) ||
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
        ...elements,
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

export interface NestedElementActionsOptions {
  element: NestedElement;
  selectedElements: NestedElement[];
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

export interface NestedElementActionEvent {
  action: string;
  elementId: number;
  item?: BulkActionItem;
  trigger: HTMLElement;
}

export interface NestedElementActionHandlers {
  perform(item: BulkActionItem, ids: number[], trigger: HTMLElement): void;
  paste(beforeId: number): void;
  move(from: number, to: number): void;
}

export function nestedElementActions({
  element,
  selectedElements,
  ids,
  index,
  count,
  editable,
  busy,
  canPaste,
  canReorder,
  canAdd,
  pasteLabel,
}: NestedElementActionsOptions): ActionItem[] {
  return withoutStraySeparators(
    (element.actionMenuItems ?? []).flatMap((item): ActionItem[] => {
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
                !selectionAllows(elementAction, selectedElements) ||
                (elementAction.key === DUPLICATE_ACTION &&
                  !canAdd(ids.length)))),
        },
      ];
    })
  );
}

export function nestedElementActionEvent(
  event: Event,
  container: HTMLElement | null | undefined
): NestedElementActionEvent | null {
  const detail = (event as CustomEvent<NestedElementActionEvent>).detail;

  return detail?.trigger && container?.contains(detail.trigger) ? detail : null;
}

export function useNestedElementActionEvents(
  container: Readonly<Ref<HTMLElement | null | undefined>>,
  options: {
    elements: MaybeRefOrGetter<NestedElement[]>;
    actionIds: (element: NestedElement) => number[];
    editable: MaybeRefOrGetter<boolean>;
    busy: MaybeRefOrGetter<boolean>;
    handlers: NestedElementActionHandlers;
    enabled?: MaybeRefOrGetter<boolean>;
  }
): void {
  useEventListener(window, 'craft:nested-element-action', (event: Event) => {
    if (!toValue(options.enabled ?? true)) {
      return;
    }

    const detail = nestedElementActionEvent(event, container.value);
    const isCopy = isCopyAction(detail?.item);
    if (
      !detail ||
      (toValue(options.busy) && !isCopy) ||
      (!toValue(options.editable) && !isCopy)
    ) {
      return;
    }

    const elements = toValue(options.elements);
    const index = elements.findIndex(
      (element) => element.id === detail.elementId
    );
    const element = elements[index];
    if (!element) {
      return;
    }

    const ids = options.actionIds(element);
    switch (detail.action) {
      case 'element-action':
        if (detail.item) {
          options.handlers.perform(detail.item, ids, detail.trigger);
        }
        break;
      case 'paste':
        options.handlers.paste(element.id);
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
