<script setup lang="ts">
  import {computed, onMounted, onUnmounted, ref} from 'vue';
  import {t} from '@craftcms/ui';
  import type {
    BulkAction,
    BulkActionItem,
  } from '@/modules/elements/types/actions';
  import type {
    ActionItemButton,
    ActionItemGroup,
    ActionItems,
  } from '@/common/types';
  import type {FormValues} from '@/modules/forms/types';
  import Text from '@/common/components/Text.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';

  const props = withDefaults(
    defineProps<{
      /** The ids of the rows currently selected. */
      selectedIds: ReadonlyArray<string | number>;
      /** The "Actions" menu's items. Not element-specific — any `http`/`download` action posts to its own `url`. */
      actions?: Array<BulkAction> | null;
      /** A separate, pinned "Set status" menu, rendered before "Actions". */
      statuses?: Array<BulkAction> | null;
      /** Extra fields merged into every `http`/`download` action's body — an element index's `elementType`/`source`/`context`, say. */
      actionContext?: FormValues;
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
  }>();

  const selectedCount = computed(() => props.selectedIds.length);

  /** Only meaningful for a real element index, where a selection can mix elements and asset-folder rows. */
  const selectionType = computed<'elements' | 'folders' | 'mixed'>(() => {
    const folderCount = props.selectedIds.filter((id) =>
      String(id).startsWith('folder:')
    ).length;

    if (folderCount === 0) {
      return 'elements';
    }

    return folderCount === selectedCount.value ? 'folders' : 'mixed';
  });

  function itemApplies(item: BulkActionItem): boolean {
    return !item.appliesTo || item.appliesTo === selectionType.value;
  }

  // Merges the live selection (under `idsField`) and `actionContext` into the
  // item's own `action`, so `runAction()` handles the request/confirm/feedback
  // with no bespoke code here. `event` actions instead merge into the event
  // detail for a client-side listener (e.g. `craft:copy-elements` below).
  function resolveItem(item: BulkActionItem): ActionItemButton {
    const variant = item.variant ?? (item.destructive ? 'danger' : undefined);

    // An imperative caller handles its own request/refresh; just wire the click.
    if (item.onClick) {
      return {
        type: 'button',
        label: item.label,
        variant,
        fill: item.fill,
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
      return {type: 'button', label: item.label, variant, fill: item.fill, disabled: true};
    }

    if (item.action.type === 'event') {
      return {
        type: 'button',
        label: item.label,
        variant,
        fill: item.fill,
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
        fill: item.fill,
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
      fill: item.fill,
      action: item.action,
      feedback: {success: {message: t('Done')}},
    } satisfies ActionItemButton;
  }

  function resolveList(list: Array<BulkAction> | null | undefined): ActionItems {
    return (list ?? []).flatMap((entry): ActionItems => {
      // A custom component (e.g. "Move to page…"), passed through as-is.
      if (entry.type === 'display') {
        return [entry];
      }

      if (entry.type === 'group') {
        const items = entry.items.filter(itemApplies).map(resolveItem);

        return items.length
          ? [{type: 'group', heading: entry.heading, items} satisfies ActionItemGroup]
          : [];
      }

      return itemApplies(entry) ? [resolveItem(entry)] : [];
    });
  }

  const menuActions = computed<ActionItems>(() => resolveList(props.actions));
  const hasMenu = computed(() => menuActions.value.length > 0);

  const statusActions = computed<ActionItems>(() => resolveList(props.statuses));
  const hasStatusMenu = computed(() => statusActions.value.length > 0);

  /**
   * Copy mirrors Craft 5: the selection is handed to the legacy
   * `Craft.cp.copyElements()`, which stores it in localStorage (for paste
   * targets like nested element managers), shows the persistent "copied"
   * notification, and broadcasts to other tabs. Nothing changes server-side,
   * so no refresh/`performed` is emitted.
   */
  function onCopyElements(event: Event) {
    if (!(event instanceof CustomEvent)) {
      return;
    }
    const detail = event.detail ?? {};
    const ids: Array<string | number> = detail.elementIds ?? props.selectedIds;

    Craft.cp?.copyElements?.(
      ids.map((id) => ({
        type: detail.elementType ?? props.elementType,
        id,
        siteId: Craft.siteId ?? null,
      }))
    );
  }

  onMounted(() =>
    window.addEventListener('craft:copy-elements', onCopyElements)
  );
  onUnmounted(() =>
    window.removeEventListener('craft:copy-elements', onCopyElements)
  );

  /**
   * `craft-action-item` bubbles `action:change-state` through its lifecycle. A
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
  <div
    class="bulk-actions-bar"
    v-if="selectedCount > 0"
    @action:change-state="onChangeState"
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
            :loading="statusProcessing"
            v-bind="attributes"
          >
            {{ t('Set status') }}
            <craft-icon name="chevron-down" slot="suffix"></craft-icon>
          </craft-button>
        </template>
      </ActionMenu>

      <ActionMenu v-if="hasMenu" :actions="menuActions">
        <template #invoker="{attributes}">
          <craft-button type="button" size="small" v-bind="attributes">
            {{ t('Actions') }}
            <craft-icon name="chevron-down" slot="suffix"></craft-icon>
          </craft-button>
        </template>
      </ActionMenu>
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
