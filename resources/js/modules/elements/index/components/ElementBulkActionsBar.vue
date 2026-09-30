<script setup lang="ts">
  import {computed, shallowRef, useTemplateRef} from 'vue';
  import {useEventListener} from '@vueuse/core';
  import {t} from '@craftcms/ui';
  import {runAction} from '@craftcms/ui/actions.mjs';
  import type {BaseAction} from '@craftcms/ui/actions.mjs';
  import {selectionAllows} from '@/modules/elements/types/actions';
  import type {
    BulkActionEventDetail,
    BulkActionItem,
    BulkActionParams,
    ElementActionSelection,
    PerformBulkAction,
  } from '@/modules/elements/types/actions';
  import type {ActionItem} from '@/common/types';
  import Text from '@/common/components/Text.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {copyElements} from '@/modules/elements/index/copy-elements';

  const props = withDefaults(
    defineProps<{
      /** The ids of the elements currently selected in the index. */
      selectedIds: ReadonlyArray<string | number>;
      /** Selected row data used by capability checks and client actions. */
      selectedElements?: ReadonlyArray<ElementActionSelection>;
      /** The serialized bulk action descriptors for the active source. */
      actions?: Array<BulkActionItem> | null;
      /** The element type class string, posted to the perform endpoint. */
      elementType: string;
      /** The active source key, posted so the server rebuilds the same query. */
      source?: string | null;
      /** The render context (e.g. `index`), posted to the perform endpoint. */
      context?: string;
      /** Additional request parameters for embedded or specialized indexes. */
      params?: BulkActionParams;
      /** Embedded indexes prepare their owner before executing an action. */
      perform?: PerformBulkAction;
    }>(),
    {
      actions: () => [],
      selectedElements: () => [],
      source: null,
      context: 'index',
      params: () => ({}),
    }
  );

  const emit = defineEmits<{
    /** Emitted after an action succeeds, so the parent can refresh + clear. */
    (e: 'performed'): void;
    /** Emitted to clear the current selection. */
    (e: 'clear'): void;
    /** Client-side action for the selected element. */
    (e: 'edit' | 'view', detail: BulkActionEventDetail): void;
  }>();

  // Set Status gets its own button alongside the menu (it's the most-reached-for
  // action), so it's pulled out of the menu list below.
  const SET_STATUS_KEY = 'CraftCms\\Cms\\Element\\Actions\\SetStatus';

  const selectedCount = computed(() => props.selectedIds.length);
  const root = useTemplateRef<HTMLElement>('root');
  const performing = shallowRef(false);

  const selectionType = computed<'elements' | 'folders' | 'mixed'>(() => {
    const folderCount = props.selectedIds.filter((id) =>
      String(id).startsWith('folder:')
    ).length;

    if (folderCount === 0) {
      return 'elements';
    }

    return folderCount === selectedCount.value ? 'folders' : 'mixed';
  });

  const availableActions = computed(() =>
    (props.actions ?? []).filter(
      (item) =>
        (!item.appliesTo || item.appliesTo === selectionType.value) &&
        selectionAllows(item, props.selectedElements)
    )
  );

  const setStatusAction = computed<BulkActionItem | undefined>(() =>
    availableActions.value.find((item) => item.key === SET_STATUS_KEY)
  );

  const menuItems = computed<Array<BulkActionItem>>(() =>
    availableActions.value.filter((item) => item.key !== SET_STATUS_KEY)
  );

  const hasMenu = computed(() => menuItems.value.length > 0);

  /**
   * Map the server descriptors to `ActionItem`s for `ActionMenu` /
   * `craft-action-item`. The server bakes the action class + its settings into
   * `action.body`; we merge the live selection (`elementIds`) and index context
   * (`elementType`, `source`, `context`) so the perform endpoint can rebuild the
   * same query the index used. `runAction()` then handles the request, confirm,
   * spinner, and feedback — no bespoke code here.
   *
   * `event` actions run client-side instead: the selection merges into the
   * event detail, and a listener (e.g. `craft:copy-elements` below) handles it.
   */
  const menuActions = computed<Array<ActionItem>>(() =>
    menuItems.value.map((item): ActionItem => {
      const variant = item.variant ?? (item.destructive ? 'danger' : undefined);

      // Disabled (e.g. not-yet-ported interactive, or non-bulk actions with
      // more than one element selected) actions render inert.
      if (
        item.disabled ||
        !item.action ||
        (item.bulk === false && selectedCount.value > 1)
      ) {
        return {
          type: 'button',
          label: item.label,
          variant,
          disabled: true,
        };
      }

      if (
        item.action.type === 'event' &&
        ['craft:edit-element', 'craft:view-element'].includes(item.action.name)
      ) {
        const type =
          item.action.name === 'craft:edit-element' ? 'edit' : 'view';

        return {
          type: 'button',
          label: item.label,
          variant,
          onClick: (event) => {
            if (!(event.currentTarget instanceof HTMLElement)) {
              return;
            }

            emit(type, {
              elementIds: props.selectedIds,
              elementType: props.elementType,
              trigger: eventTrigger(event.currentTarget),
            });
          },
        };
      }

      if (item.action.type === 'event') {
        return {
          type: 'button',
          label: item.label,
          variant,
          action: {
            ...item.action,
            detail: {
              ...item.action.detail,
              elementType: props.elementType,
              elementIds: props.selectedIds,
            },
          },
        } satisfies ActionItem;
      }

      if (item.action.type === 'http' || item.action.type === 'download') {
        if (!props.perform) {
          return {
            type: 'button',
            label: item.label,
            variant,
            action: request(item),
            feedback: {success: {message: t('Done')}},
          } satisfies ActionItem;
        }

        return {
          label: item.label,
          variant,
          onClick: (event) => void performItem(item, event),
        };
      }

      return {
        type: 'button',
        label: item.label,
        variant,
        action: item.action,
        feedback: {success: {message: t('Done')}},
      } satisfies ActionItem;
    })
  );

  function request(
    item: BulkActionItem,
    overrides: BulkActionParams = {}
  ): BaseAction {
    const action = item.action;

    if (!action || (action.type !== 'http' && action.type !== 'download')) {
      throw new Error('Bulk action does not contain an executable request.');
    }

    return {
      ...action,
      body: {
        ...action.body,
        elementType: props.elementType,
        source: props.source,
        context: props.context,
        elementIds: props.selectedIds,
        ...props.params,
        ...overrides,
      },
    };
  }

  async function execute(
    item: BulkActionItem,
    overrides: BulkActionParams = {},
    sourceEvent?: Event
  ): Promise<boolean> {
    performing.value = true;

    try {
      const action = request(item, overrides);
      const trigger =
        sourceEvent?.currentTarget instanceof Element
          ? sourceEvent.currentTarget
          : undefined;

      await runAction(action, {trigger, sourceEvent});
      window.Craft?.cp?.displayNotice?.(t('Done'));

      if (action.type === 'http') {
        emit('performed');
      }

      return true;
    } catch (cause) {
      window.Craft?.cp?.displayError?.(
        cause instanceof Error ? cause.message : t('A server error occurred.')
      );

      return false;
    } finally {
      performing.value = false;
    }
  }

  async function performItem(item: BulkActionItem, sourceEvent?: Event) {
    if (performing.value) {
      return;
    }

    const run = (overrides?: BulkActionParams) =>
      execute(item, overrides, sourceEvent);

    if (props.perform) {
      try {
        await props.perform(item, run);
      } catch (cause) {
        window.Craft?.cp?.displayError?.(
          cause instanceof Error ? cause.message : t('A server error occurred.')
        );
      }

      return;
    }

    await run();
  }

  /**
   * Copy mirrors Craft 5: the selection is handed to the legacy
   * `Craft.cp.copyElements()`, which stores it in localStorage (for paste
   * targets like nested element managers), shows the persistent "copied"
   * notification, and broadcasts to other tabs. Nothing changes server-side,
   * so no refresh/`performed` is emitted.
   */
  function onCopyElements(event: Event) {
    if (!owns(event)) {
      return;
    }
    const detail = event.detail ?? {};
    const ids: ReadonlyArray<string | number> =
      detail.elementIds ?? props.selectedIds;

    copyElements(
      detail.elementType ?? props.elementType,
      ids,
      detail.elements ?? props.selectedElements
    );
  }

  function owns(event: Event): event is CustomEvent<{
    trigger: HTMLElement;
    elementIds?: ReadonlyArray<string | number>;
    elementType?: string;
    elements?: ReadonlyArray<ElementActionSelection>;
  }> {
    if (!(event instanceof CustomEvent)) {
      return false;
    }

    const trigger = event.detail?.trigger;

    return (
      trigger instanceof HTMLElement && Boolean(root.value?.contains(trigger))
    );
  }

  function eventTrigger(trigger: HTMLElement): HTMLElement {
    const menu = trigger.closest('craft-action-menu');

    return menu?.querySelector<HTMLElement>('[slot="invoker"]') ?? trigger;
  }

  useEventListener(window, 'craft:copy-elements', onCopyElements);
  /**
   * `craft-action-item` bubbles `craft-state-change` through its lifecycle. A
   * successful `http` action means the selection was acted on, so refresh the
   * table + clear selection. (Other states drive the item's own spinner/feedback.)
   */
  function onChangeState(event: Event) {
    if (!(event instanceof CustomEvent)) {
      return;
    }
    const detail = event.detail;
    if (detail?.state === 'success' && detail?.actionType === 'http') {
      emit('performed');
    }
  }

  function setStatus(status: 'enabled' | 'disabled') {
    const item = setStatusAction.value;
    if (!item?.action || item.action.type !== 'http') {
      return;
    }

    void performItem({
      ...item,
      action: {...item.action, body: {...item.action.body, status}},
    });
  }
</script>

<template>
  <div
    ref="root"
    class="bulk-actions-bar"
    v-if="selectedCount > 0"
    @craft-state-change="onChangeState"
  >
    <div class="bulk-actions-bar__selection">
      <Text
        as="span"
        class="bulk-actions-bar__count"
        template="{count, plural, =1{# selected} other{# selected}}"
        :params="{count: selectedCount}"
      />
      <craft-button
        type="button"
        size="small"
        variant="plain"
        @click="emit('clear')"
      >
        {{ t('Clear selection') }}
      </craft-button>
    </div>

    <div class="bulk-actions-bar__actions">
      <craft-action-menu
        v-if="setStatusAction"
        .disabled="setStatusAction.disabled || performing"
      >
        <craft-button
          slot="invoker"
          type="button"
          size="small"
          .loading="performing"
        >
          {{ setStatusAction.label }}
          <craft-icon name="chevron-down" slot="suffix"></craft-icon>
        </craft-button>

        <div slot="content">
          <craft-action-item @click="setStatus('enabled')">
            <craft-indicator fill="success"></craft-indicator>
            {{ t('Enabled') }}
          </craft-action-item>
          <craft-action-item @click="setStatus('disabled')">
            <craft-indicator fill="danger"></craft-indicator>
            {{ t('Disabled') }}
          </craft-action-item>
        </div>
      </craft-action-menu>

      <ActionMenu v-if="hasMenu" :actions="menuActions">
        <template #invoker="{attributes}">
          <craft-button type="button" size="small" v-bind="attributes">
            {{ t('Actions') }}
            <craft-icon name="chevron-down" slot="suffix"></craft-icon>
          </craft-button>
        </template>
      </ActionMenu>
      <slot />
    </div>
  </div>
</template>

<style scoped lang="scss">
  .bulk-actions-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--c-spacing-md);
    flex-wrap: wrap;
    width: 100%;
  }

  .bulk-actions-bar__selection {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .bulk-actions-bar__count {
    font-weight: 600;
  }

  .bulk-actions-bar__actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    flex-wrap: wrap;
  }
</style>
