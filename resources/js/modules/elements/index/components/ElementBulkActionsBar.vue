<script setup lang="ts">
  import {computed, shallowRef, useTemplateRef} from 'vue';
  import {t} from '@craftcms/ui';
  import {
    elementActionRequest,
    runElementAction,
  } from '@/modules/elements/index/element-actions';
  import {selectionAllows} from '@/modules/elements/types/actions';
  import type {
    BulkActionEventDetail,
    BulkActionItem,
    BulkActionParams,
    ElementActionSelection,
    PerformBulkAction,
  } from '@/modules/elements/types/actions';
  import type {ActionItem} from '@/common/types';
  import BulkActionsBarShell from '@/modules/elements/components/BulkActionsBarShell.vue';
  import {useBulkActionSelection} from '@/modules/elements/composables/useBulkActionSelection';

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

  const bar = useTemplateRef('bar');
  const performing = shallowRef(false);

  const {selectedCount, appliesToSelection, onOwnedEvent, invokerFor} =
    useBulkActionSelection({
      selectedIds: () => props.selectedIds,
      root: () => bar.value?.root,
      elementType: () => props.elementType,
      selectedElements: () => props.selectedElements,
    });

  const availableActions = computed(() =>
    (props.actions ?? []).filter(
      (item) =>
        appliesToSelection(item) &&
        selectionAllows(item, props.selectedElements)
    )
  );

  const setStatusAction = computed<BulkActionItem | undefined>(() =>
    availableActions.value.find((item) => item.key === SET_STATUS_KEY)
  );

  const menuItems = computed<Array<BulkActionItem>>(() =>
    availableActions.value.filter((item) => item.key !== SET_STATUS_KEY)
  );

  /**
   * Map the server descriptors to `ActionItem`s for `ActionMenu` /
   * `craft-action-item`. The server bakes the action class + its settings into
   * `action.body`; we merge the live selection (`elementIds`) and index context
   * (`elementType`, `source`, `context`) so the perform endpoint can rebuild the
   * same query the index used. `runAction()` then handles the request, confirm,
   * spinner, and feedback — no bespoke code here.
   *
   * `event` actions run client-side instead: the selection merges into the
   * event detail, and a listener (e.g. `craft:copy-elements`) handles it.
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
              trigger: invokerFor(event.currentTarget),
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
            action: elementActionRequest(item, requestParams()),
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

  function requestParams(overrides: BulkActionParams = {}): BulkActionParams {
    return {
      elementType: props.elementType,
      source: props.source,
      context: props.context,
      elementIds: props.selectedIds,
      ...props.params,
      ...overrides,
    };
  }

  async function execute(
    item: BulkActionItem,
    overrides: BulkActionParams = {},
    sourceEvent?: Event
  ): Promise<boolean> {
    performing.value = true;

    try {
      const trigger =
        sourceEvent?.currentTarget instanceof Element
          ? sourceEvent.currentTarget
          : undefined;

      await runElementAction(item, requestParams(overrides), {
        trigger,
        sourceEvent,
      });

      if (item.action?.type === 'http') {
        emit('performed');
      }

      return true;
    } catch {
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
   * Preview file is single-element, so it previews the one selected asset.
   */
  onOwnedEvent('craft:preview-file', (event) => {
    const [id] = event.detail.elementIds ?? props.selectedIds;

    if (id !== undefined) {
      new Craft.PreviewFileModal(Number(id));
    }
  });

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
  <BulkActionsBarShell
    ref="bar"
    :selected-count="selectedCount"
    :actions="menuActions"
    @performed="emit('performed')"
    @clear="emit('clear')"
  >
    <template #status>
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
    </template>

    <slot />
  </BulkActionsBarShell>
</template>
