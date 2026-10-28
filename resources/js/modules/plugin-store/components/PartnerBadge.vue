<script setup lang="ts">
  const {kind} = defineProps<{kind: 'craft' | 'commerce' | 'enterprise'}>();

  const icons = {
    craft: 'check',
    commerce: 'credit-card',
    enterprise: 'building',
  };
</script>

<template>
  <span class="partner-badge">
    <craft-icon :name="icons[kind]"></craft-icon>
  </span>
</template>

<style scoped>
  .partner-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    inline-size: 1.25rem;
    block-size: 1.25rem;
    border: 1px solid currentColor;
    border-radius: var(--c-radius-full);
    color: var(--c-color-accent-fill-loud);
    font-size: 0.7rem;
  }
</style>
