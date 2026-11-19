<script setup lang="ts">
  /**
   * Saves the index's current view: to its source when the source can take
   * it, with saving a new source in the menu like an element's Save; otherwise
   * just as a new source.
   */
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';

  const props = withDefaults(
    defineProps<{
      /** The source's name, when its saved view can be overwritten. */
      updatableSource?: string | null;
      /** Why the source can't take these changes, if it can't. */
      updateBlockedReason?: string | null;
      disabled?: boolean;
      /** Matches the buttons beside it. */
      size?: 'small' | 'medium';
    }>(),
    {size: 'small'}
  );

  const emit = defineEmits<{
    update: [];
    'save-new': [];
  }>();

  const canUpdate = computed(
    () => Boolean(props.updatableSource) && !props.updateBlockedReason
  );
  const saveActions = computed<ActionItem[]>(() => [
    {label: t('Save as new…'), onClick: () => emit('save-new')},
  ]);
</script>

<template>
  <craft-button-group v-if="canUpdate">
    <craft-button
      class="save-view__save"
      type="button"
      :size="size"
      :variant="ButtonVariant.Solid"
      .disabled="disabled"
      @click="emit('update')"
      >{{ t('Save') }}</craft-button
    >
    <ActionMenu icon="chevron-down" :actions="saveActions">
      <template #invoker="{label}">
        <craft-button
          slot="invoker"
          :variant="ButtonVariant.Solid"
          type="button"
          :size="size"
          icon
        >
          <craft-icon name="chevron-down" :label="label"></craft-icon>
        </craft-button>
      </template>
    </ActionMenu>
  </craft-button-group>
  <craft-button
    v-else
    type="button"
    :size="size"
    :variant="ButtonVariant.Solid"
    .disabled="disabled"
    @click="emit('save-new')"
    >{{ t('Save as new') }}</craft-button
  >
</template>

<style scoped lang="scss">
  .save-view__save {
    min-width: 3.375rem;
  }
</style>
