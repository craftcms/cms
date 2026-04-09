<script setup lang="ts">
  withDefaults(
    defineProps<{
      isSortable?: boolean;
    }>(),
    {
      isSortable: false,
    }
  );
</script>

<template>
  <button v-if="isSortable"><slot /></button>

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
