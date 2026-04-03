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

<style scoped lang="scss"></style>
