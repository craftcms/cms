<script setup lang="ts">
  /**
   * The frame a bulk-actions bar draws while rows are selected: the count, a
   * "Clear selection" button, and the "Actions" menu. A bar puts its own
   * pinned menus in the `status` slot, before "Actions", and anything else in
   * the default slot, after it.
   */
  import {useTemplateRef} from 'vue';
  import {t} from '@craftcms/ui';
  import type {ActionItems} from '@/common/types';
  import Text from '@/common/components/Text.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';

  defineProps<{
    selectedCount: number;
    /** The "Actions" menu's items; the menu is left out when there are none. */
    actions: ActionItems;
  }>();

  const emit = defineEmits<{
    /** An `http` action item succeeded, so the parent can refresh + clear. */
    (e: 'performed'): void;
    (e: 'clear'): void;
  }>();

  const root = useTemplateRef<HTMLElement>('root');

  defineExpose({root});

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
      <slot name="status" />

      <ActionMenu v-if="actions.length > 0" :actions="actions">
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
