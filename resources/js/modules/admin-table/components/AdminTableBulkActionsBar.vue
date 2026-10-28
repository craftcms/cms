<script setup lang="ts">
  import {computed, ref, useTemplateRef} from 'vue';
  import {t} from '@craftcms/ui';
  import type {
    BulkAction,
    BulkActionEventDetail,
    BulkActionItem,
  } from '@/modules/elements/types/actions';
  import type {
    ActionItemButton,
    ActionItemGroup,
    ActionItems,
  } from '@/common/types';
  import type {UiValues} from '@/modules/ui/types';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import BulkActionsBarShell from '@/modules/elements/components/BulkActionsBarShell.vue';
  import {useBulkActionSelection} from '@/modules/elements/composables/useBulkActionSelection';

  const props = withDefaults(
    defineProps<{
      /** The ids of the rows currently selected. */
      selectedIds: ReadonlyArray<string | number>;
      /** The "Actions" menu's items. Not element-specific — any `http`/`download` action posts to its own `url`. */
      actions?: Array<BulkAction> | null;
      /** A separate, pinned "Set status" menu, rendered before "Actions". */
      statuses?: Array<BulkAction> | null;
      /** Extra fields merged into every `http`/`download` action's body — an element index's `elementType`/`source`/`context`, say. */
      actionContext?: UiValues;
      /** The body key the selection posts under. */
      idsField?: string;
      /** Only used by the `craft:copy-elements` listener below; irrelevant outside an element index. */
      elementType?: string;
    }>(),
    {
      actions: () => [],
      statuses: () => [],
      idsField: 'elementIds',
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

  const bar = useTemplateRef('bar');

  const {selectedCount, appliesToSelection, onOwnedEvent, invokerFor} =
    useBulkActionSelection({
      selectedIds: () => props.selectedIds,
      root: () => bar.value?.root,
      elementType: () => props.elementType,
    });

  // Merges the live selection (under `idsField`) and `actionContext` into the
  // item's own `action`, so `runAction()` handles the request/confirm/feedback
  // with no bespoke code here. `event` actions instead merge into the event
  // detail for a client-side listener (e.g. `craft:copy-elements`).
  function resolveItem(item: BulkActionItem): ActionItemButton {
    const variant = item.variant ?? (item.destructive ? 'danger' : undefined);

    // An imperative caller handles its own request/refresh; just wire the click.
    if (item.onClick) {
      return {
        type: 'button',
        label: item.label,
        variant,
        status: item.fill,
        disabled: item.disabled,
        onClick: item.onClick,
      } satisfies ActionItemButton;
    }

    // Disabled (e.g. not-yet-ported interactive, or non-bulk actions with
    // more than one row selected) actions render inert.
    if (
      item.disabled ||
      !item.action ||
      (item.bulk === false && selectedCount.value > 1)
    ) {
      return {
        type: 'button',
        label: item.label,
        variant,
        status: item.fill,
        disabled: true,
      };
    }

    if (item.action.type === 'event') {
      return {
        type: 'button',
        label: item.label,
        variant,
        status: item.fill,
        action: {
          ...item.action,
          detail: {
            ...item.action.detail,
            elementType: props.elementType,
            [props.idsField ?? 'elementIds']: props.selectedIds,
          },
        },
      } satisfies ActionItemButton;
    }

    if (item.action.type === 'http' || item.action.type === 'download') {
      return {
        type: 'button',
        label: item.label,
        variant,
        status: item.fill,
        action: {
          ...item.action,
          body: {
            ...item.action.body,
            ...props.actionContext,
            [props.idsField ?? 'elementIds']: props.selectedIds,
          },
        },
        feedback: {success: {message: t('Done')}},
      } satisfies ActionItemButton;
    }

    return {
      type: 'button',
      label: item.label,
      variant,
      status: item.fill,
      action: item.action,
      feedback: {success: {message: t('Done')}},
    } satisfies ActionItemButton;
  }

  function resolveList(
    list: Array<BulkAction> | null | undefined
  ): ActionItems {
    return (list ?? []).flatMap((entry): ActionItems => {
      // A custom component (e.g. "Move to page…"), passed through as-is.
      if (entry.type === 'display') {
        return [entry];
      }

      if (entry.type === 'group') {
        const items = entry.items.filter(appliesToSelection).map(resolveItem);

        return items.length
          ? [
              {
                type: 'group',
                heading: entry.heading,
                items,
              } satisfies ActionItemGroup,
            ]
          : [];
      }

      return appliesToSelection(entry) ? [resolveItem(entry)] : [];
    });
  }

  const menuActions = computed<ActionItems>(() => resolveList(props.actions));

  const statusActions = computed<ActionItems>(() =>
    resolveList(props.statuses)
  );
  const hasStatusMenu = computed(() => statusActions.value.length > 0);

  function onElementEvent(
    event: CustomEvent<{
      trigger: HTMLElement;
      elementIds?: ReadonlyArray<string | number>;
      elementType?: string;
    }>,
    type: 'edit' | 'view'
  ): void {
    const detail = {
      elementIds: event.detail?.elementIds ?? props.selectedIds,
      elementType: event.detail?.elementType ?? props.elementType ?? '',
      trigger: invokerFor(event.detail.trigger),
    };

    emit(type, detail);
  }

  onOwnedEvent('craft:edit-element', (event) => onElementEvent(event, 'edit'));
  onOwnedEvent('craft:view-element', (event) => onElementEvent(event, 'view'));

  // One shared spinner across "Set status"'s items; scoped to that menu only —
  // `ActionMenu` has no declared emits, so an unscoped listener would also
  // catch "Actions" items bubbling through the same bar.
  const statusProcessing = ref(false);
  function onStatusActionChangeState(event: Event) {
    if (!(event instanceof CustomEvent)) {
      return;
    }
    const state = event.detail?.state;
    if (state === 'loading') {
      statusProcessing.value = true;
    } else if (state === 'success' || state === 'error') {
      statusProcessing.value = false;
    }
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
      <ActionMenu
        v-if="hasStatusMenu"
        :actions="statusActions"
        :label="t('Set status')"
        @action:change-state="onStatusActionChangeState"
      >
        <template #invoker="{attributes}">
          <craft-button
            type="button"
            size="small"
            .loading="statusProcessing"
            v-bind="attributes"
          >
            {{ t('Set status') }}
            <craft-icon name="chevron-down" slot="suffix"></craft-icon>
          </craft-button>
        </template>
      </ActionMenu>
    </template>
  </BulkActionsBarShell>
</template>
