<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';

  const emit = defineEmits<{
    (e: 'click'): void;
  }>();
  const props = withDefaults(
    defineProps<{
      confirm?: string;
      disabled?: boolean;
      label?: string;
      icon?: string;
    }>(),
    {disabled: false, label: t('Delete item'), icon: 'x'}
  );

  function handleClick(): void {
    if (props.disabled) {
      return;
    }

    if (props.confirm && !window.confirm(props.confirm)) {
      return;
    }

    emit('click');
  }
</script>

<template>
  <craft-button
    type="button"
    @click="handleClick"
    :aria-disabled="disabled ? 'true' : undefined"
    size="small"
    variant="danger-plain"
    v-bind="$attrs"
  >
    <craft-icon :name="icon" :label="label"></craft-icon>
  </craft-button>
</template>

<style scoped lang="scss">
  craft-button[aria-disabled='true'] {
    cursor: default;
    opacity: 0.25;
  }
</style>
