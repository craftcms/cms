<script setup lang="ts"></script>

<template>
  <div class="dev-mode">
    <div
      class="inline-flex py-1 px-2 bg-slate-900 text-slate-100 font-mono text-xs rounded-lg"
    >
      Dev Mode is enabled
    </div>
  </div>
</template>

<style scoped lang="scss">
  .dev-mode {
    padding: calc(var(--spacing) * 2);
    text-align: center;
    background-image: repeating-linear-gradient(
      -45deg,
      transparent,
      transparent 12px,
      var(--c-color-static-warning-fill) 12px,
      var(--c-color-static-warning-fill) 20px
    );
    background-color: var(--color-slate-900);
  }
</style>
