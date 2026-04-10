<script setup lang="ts">
  withDefaults(
    defineProps<{
      isSortable?: boolean;
      sortInstructionsId: string;
    }>(),
    {
      isSortable: false,
    }
  );

  defineEmits<{
    sortColumn: [event: MouseEvent];
  }>();
</script>

<template>
  <button
    v-if="isSortable"
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
      top: 0;
      left: 0;
      content: '';
      width: 100%;
      height: 100%;
    }
  }
</style>
