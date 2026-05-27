<script setup lang="ts">
  defineProps<{
    data: any;
  }>();
</script>

<template>
  <pre v-bind="$attrs">{{ JSON.stringify(data, null, 2) }}</pre>
</template>

<style scoped lang="scss">
  pre {
    font-size: 0.7rem;
    padding: var(--c-spacing-md);
    border: 1px solid var(--color-slate-400);
    background-color: var(--color-slate-50);
    border-radius: var(--c-radius-md);
    overflow: auto;
  }
</style>
