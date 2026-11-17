import {useEventListener} from '@vueuse/core';
import {computed, inject, onBeforeUnmount, watch, type Ref} from 'vue';
import type {Selectable} from '@/common/composables/useSelectable';
import {
  MATRIX_SELECTION_ACTION,
  matrixField,
  selectionMenuItem,
  syncSelectionMenu,
  withoutStraySeparators,
} from '@/modules/matrix/selection-menu';
import {FieldActionItems} from '../runtime';
import type {NestedElementValue} from '../types';
import {
  BLOCK_ACTION_EVENT,
  type BlockActionDetail,
} from './nested-element-block-menu';
import type {BlockStatus} from './nested-element-blocks';

/** What the menus can ask of the field's blocks. */
export type BlockCommands = {
  setCollapsed(uids: readonly string[], collapsed: boolean): void;
  setStatus(
    uids: readonly string[],
    scope: 'global' | 'site',
    enabled: boolean
  ): void;
  remove(uids: readonly string[]): void;
  add(entryType: string, beforeUid: string): Promise<void>;
  duplicate(uid: string): Promise<void>;
  copy(uid: string): void;
  paste(beforeUid: string): Promise<void>;
};

type MatrixFieldEventsOptions = {
  host: Readonly<Ref<HTMLElement | undefined>>;
  value: () => NestedElementValue;
  selection: Selectable<string>;
  /** The blocks a menu action on `uid` applies to. */
  actionTargets: (uid: string) => string[];
  isCollapsed: (uid: string) => boolean;
  status: BlockStatus;
  commands: BlockCommands;
};

/**
 * The block and field menus' actions, which arrive as `window` events, and the
 * selection state the field's own menu reads back.
 */
export function useMatrixFieldEvents(options: MatrixFieldEventsOptions) {
  const {host, selection, commands, status} = options;

  /**
   * `runAction()` dispatches `event` actions on `window`, so every Matrix on the
   * page hears every block action. Scope by the invoking element: the menu keeps
   * its content in place, so the trigger is still inside the block it belongs to.
   */
  function onBlockAction(event: Event): void {
    const detail = (event as CustomEvent<BlockActionDetail>).detail;

    if (!detail || !host.value) {
      return;
    }

    const trigger = detail.trigger;
    const owned =
      trigger instanceof HTMLElement &&
      trigger.closest('[data-matrix-field]') === host.value;

    if (!owned) {
      return;
    }

    const targets = options.actionTargets(detail.uid);

    switch (detail.action) {
      case 'collapse':
      case 'expand':
        commands.setCollapsed(targets, detail.action === 'collapse');

        return;

      case 'disable':
      case 'enable':
        commands.setStatus(targets, 'global', detail.action === 'enable');

        return;

      case 'disableForSite':
      case 'enableForSite':
        commands.setStatus(targets, 'site', detail.action === 'enableForSite');

        return;

      case 'disableGlobally':
      case 'enableGlobally':
        commands.setStatus(
          targets,
          'global',
          detail.action === 'enableGlobally'
        );

        return;

      case 'delete':
        commands.remove(targets);

        return;

      case 'add':
        void commands.add(detail.entryType ?? '', detail.uid);

        return;

      case 'duplicate':
        void commands.duplicate(detail.uid);

        return;

      case 'copy':
        commands.copy(detail.uid);

        return;

      case 'paste':
        void commands.paste(detail.uid);

        return;

      default:
        return;
    }
  }

  /**
   * The field's own "Expand/Collapse all blocks" items. `modules/fields` handles
   * these through each block's MatrixEntry controller, which only server-rendered
   * blocks have — the ones here are Vue's, so this control applies them itself.
   *
   * Scoped by field rather than by the Matrix host the way block actions are: the
   * invoking item lives in the field's header, outside the input. Comparing the
   * fields also keeps a nested Matrix out of its parent's reach.
   */
  function onToggleAll(event: Event): void {
    const detail = (
      event as CustomEvent<{collapse?: boolean; trigger?: unknown}>
    ).detail;

    if (!fromOwnField(detail?.trigger)) {
      return;
    }

    commands.setCollapsed(options.value().sortOrder, detail?.collapse === true);
  }

  function fromOwnField(trigger: unknown): boolean {
    const field = host.value ? matrixField(host.value) : null;

    return (
      Boolean(field) &&
      trigger instanceof HTMLElement &&
      matrixField(trigger) === field
    );
  }

  /**
   * The field menu's "… selected blocks" items, which apply to the selection
   * rather than to one block. Scoped by field, like "Collapse all blocks".
   */
  function onSelectionAction(event: Event): void {
    const detail = (event as CustomEvent<{action?: string; trigger?: unknown}>)
      .detail;

    if (!fromOwnField(detail?.trigger)) {
      return;
    }

    const targets = [...selection.selectedIds.value];

    switch (detail?.action) {
      case 'select':
        selection.selectAll(true);
        break;
      case 'deselect':
        selection.clear();
        break;
      case 'collapse':
      case 'expand':
        if (targets.length) {
          commands.setCollapsed(targets, detail.action === 'collapse');
        }
        break;
      case 'disable':
      case 'enable':
        if (targets.length) {
          commands.setStatus(targets, 'global', detail.action === 'enable');
        }
        break;
      case 'disableForSite':
      case 'enableForSite':
        if (targets.length) {
          commands.setStatus(
            targets,
            'site',
            detail.action === 'enableForSite'
          );
        }
        break;
      case 'disableGlobally':
      case 'enableGlobally':
        if (targets.length) {
          commands.setStatus(
            targets,
            'global',
            detail.action === 'enableGlobally'
          );
        }
        break;
    }
  }

  /**
   * The field's own menu reads for the selection: its "… selected blocks" items
   * show once there is one, and "Copy all blocks" copies just those blocks.
   */
  const fieldActionItems = inject(FieldActionItems, undefined);
  const selectionState = computed(() => {
    const selected = selection.selectedIds.value;
    const sortOrder = options.value().sortOrder;

    return {
      total: sortOrder.length,
      anyCollapsed: sortOrder.some((uid) => options.isCollapsed(uid)),
      anyExpanded: sortOrder.some((uid) => !options.isCollapsed(uid)),
      count: selected.length,
      collapsed: selected.length > 0 && selected.every(options.isCollapsed),
      disabled: selected.length > 0 && selected.every(status.isDisabled),
      globallyDisabled:
        selected.length > 0 && selected.every(status.isGloballyDisabled),
      anyGloballyDisabled: selected.some(status.isGloballyDisabled),
      disabledForSite:
        selected.length > 0 && selected.every(status.isDisabledForSite),
    };
  });

  watch(
    [selectionState, host],
    () => {
      const field = host.value?.closest('craft-field');
      if (
        field?.parentElement?.matches(
          'craft-entry-field-layout-ui[data-field-path]'
        )
      ) {
        syncSelectionMenu(matrixField(host.value!)!);
      }
    },
    {flush: 'post'}
  );

  if (fieldActionItems) {
    fieldActionItems.value = (items) =>
      withoutStraySeparators(
        items.map((item) => selectionMenuItem(item, selectionState.value))
      );
  }

  useEventListener(window, BLOCK_ACTION_EVENT, onBlockAction);
  useEventListener(window, 'craft:matrix-toggle-all', onToggleAll);
  useEventListener(window, MATRIX_SELECTION_ACTION, onSelectionAction);
  onBeforeUnmount(() => {
    if (fieldActionItems) {
      fieldActionItems.value = undefined;
    }
  });
}
