<script setup lang="ts"></script>

<template>
  <div class="dev-mode">
    <div
      class="inline-flex cp:py-1 cp:px-2 cp:bg-slate-900 cp:text-slate-100 cp:font-mono cp:text-xs cp:rounded-lg"
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
