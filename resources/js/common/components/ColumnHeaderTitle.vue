<script setup lang="ts">
  withDefaults(
    defineProps<{
      isSortable?: boolean;
      disabled?: boolean;
      sortInstructionsId: string;
    }>(),
    {
      isSortable: false,
      disabled: false,
    }
  );

  defineEmits<{
    sortColumn: [event: MouseEvent];
  }>();
</script>

<template>
  <button
    v-if="isSortable"
    type="button"
    :disabled="disabled"
    @click="$emit('sortColumn', $event)"
    :aria-describedby="sortInstructionsId"
  >
    <slot />
  </button>

  <slot v-else />
</template>

<style scoped lang="scss">
  button {
    all: unset;

    &::after {
      position: absolute;
      inset-block-start: 0;
      inset-inline-start: 0;
      content: '';
      width: 100%;
      height: 100%;
    }
  }
</style>
