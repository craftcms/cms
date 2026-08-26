<script setup lang="ts">
  import {computed, onMounted, onUnmounted} from 'vue';
  import {t} from '@craftcms/ui';
  import type {BulkActionItem} from '@/modules/elements/types/actions';
  import type {ActionItem} from '@/common/types';
  import Text from '@/common/components/Text.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {useForm} from '@inertiajs/vue3';
  import PerformElementActionController from '@actions/Elements/PerformElementActionController';

  const props = withDefaults(
    defineProps<{
      /** The ids of the elements currently selected in the index. */
      selectedIds: ReadonlyArray<string | number>;
      /** The serialized bulk action descriptors for the active source. */
      actions?: Array<BulkActionItem> | null;
      /** The element type class string, posted to the perform endpoint. */
      elementType: string;
      /** The active source key, posted so the server rebuilds the same query. */
      source?: string | null;
      /** The render context (e.g. `index`), posted to the perform endpoint. */
      context?: string;
    }>(),
    {
      actions: () => [],
      source: null,
      context: 'index',
    }
  );

  const emit = defineEmits<{
    /** Emitted after an action succeeds, so the parent can refresh + clear. */
    (e: 'performed'): void;
    /** Emitted to clear the current selection. */
    (e: 'clear'): void;
  }>();

  // Set Status gets its own button alongside the menu (it's the most-reached-for
  // action), so it's pulled out of the menu list below.
  const SET_STATUS_KEY = 'CraftCms\\Cms\\Element\\Actions\\SetStatus';

  const selectedCount = computed(() => props.selectedIds.length);

  const setStatusAction = computed<BulkActionItem | undefined>(() =>
    (props.actions ?? []).find((item) => item.key === SET_STATUS_KEY)
  );

  const menuItems = computed<Array<BulkActionItem>>(() =>
    (props.actions ?? []).filter((item) => item.key !== SET_STATUS_KEY)
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
        return {
          type: 'button',
          label: item.label,
          variant,
          action: {
            ...item.action,
            body: {
              ...item.action.body,
              elementType: props.elementType,
              source: props.source,
              context: props.context,
              elementIds: props.selectedIds,
            },
          },
          feedback: {success: {message: t('Done')}},
        } satisfies ActionItem;
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

  const action = useForm({
    elementIds: props.selectedIds,
    elementAction: SET_STATUS_KEY,
    elementType: props.elementType,
    context: props.context,
    status: '',
    source: props.source,
  });

  function setStatus(status: 'enabled' | 'disabled') {
    action
      .transform((data) => {
        data.status = status;
        data.elementIds = props.selectedIds;

        return data;
      })
      .post(PerformElementActionController.url(), {
        only: ['data', 'flash', 'pagination'],
        preserveState: false,
      });
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
      <craft-action-menu
        v-if="setStatusAction"
        :disabled="setStatusAction.disabled"
      >
        <craft-button
          slot="invoker"
          type="button"
          size="small"
          :loading="action.processing"
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
