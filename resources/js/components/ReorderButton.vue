<script setup lang="ts">
  import {t} from '@craftcms/cp';

  const emit = defineEmits<{
    (e: 'click:up'): void;
    (e: 'click:down'): void;
  }>();
  withDefaults(
    defineProps<{
      label?: string;
      position?: 'first' | 'middle' | 'last';
    }>(),
    {label: t('Reorder'), position: 'middle'}
  );
</script>

<template>
  <craft-action-menu>
    <craft-button
      slot="invoker"
      type="button"
      icon
      size="small"
      appearance="plain"
      v-bind="$attrs"
    >
      <craft-icon name="custom-icons/grip-dots" :label="label"></craft-icon>
    </craft-button>

    <div slot="content">
      <craft-action-item
        icon="arrow-up"
        @click="emit('click:up')"
        :disabled="position === 'first'"
        >{{ t('Move up') }}</craft-action-item
      >
      <craft-action-item
        icon="arrow-down"
        @click="emit('click:down')"
        :disabled="position === 'last'"
        >{{ t('Move down') }}</craft-action-item
      >
    </div>
  </craft-action-menu>
</template>

<style scoped lang="scss">
  craft-button {
    cursor: move;
  }
</style>
