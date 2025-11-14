<script setup lang="ts">
  withDefaults(
    defineProps<{
      isActive?: boolean;
    }>(),
    {isActive: false}
  );
</script>

<template>
  <div class="modal" v-if="isActive">
    <div class="modal__body">
      <slot></slot>
    </div>
  </div>
</template>

<style scoped>
  .modal {
    position: fixed;
    inset: 0;
    display: grid;
    overflow: hidden;
    height: 100%;
    width: 100%;
    align-content: center;
    justify-content: center;
  }

  .modal__body {
    width: 100%;
    height: 100%;
    max-width: calc(100vw - (var(--c-spacing-lg) * 2));
    max-height: calc(100vh - (var(--c-spacing-lg) * 2));
    box-shadow: var(--c-modal-shadow);
    overflow-y: scroll;
    -webkit-overflow-scrolling: touch;
    border-radius: var(--c-modal-radius);
    border: var(--c-modal-border);
  }
</style>
