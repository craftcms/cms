<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';

  const emit = defineEmits<{
    (e: 'click'): void;
  }>();
  const props = withDefaults(
    defineProps<{
      confirm?: string;
      label?: string;
      icon?: string;
    }>(),
    {label: t('Delete item'), icon: 'x'}
  );

  function handleClick(): void {
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
    size="small"
    variant="danger-plain"
    v-bind="$attrs"
  >
    <craft-icon :name="icon" :label="label"></craft-icon>
  </craft-button>
</template>

<style scoped lang="scss"></style>
