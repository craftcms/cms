<script setup lang="ts">
  import CodeHighlight from '@/common/components/CodeHighlight.vue';

  defineProps<{
    data: any;
  }>();
</script>

<template>
  <CodeHighlight language="json" :value="data" class="var-dump" />
</template>

<style scoped lang="scss">
  .var-dump {
    font-size: 0.7rem;
    padding: var(--c-spacing-md);
    border: 1px solid var(--c-color-neutral-border-quiet);
    background-color: var(--c-color-neutral-fill-quiet);
    border-radius: var(--c-radius-md);
    overflow: auto;
  }
</style>
