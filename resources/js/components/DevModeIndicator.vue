<script setup lang="ts"></script>

<template>
  <div class="dev-mode">
    <div
      class="tw:inline-flex tw:py-1 tw:px-2 tw:bg-slate-900 tw:text-slate-100 tw:font-mono tw:text-xs tw:rounded-lg"
    >
      Dev Mode is enabled
    </div>
  </div>
</template>

<style scoped lang="scss">
  .dev-mode {
    padding: calc(var(--tw-spacing) * 2);
    text-align: center;
    background-image: repeating-linear-gradient(
      -45deg,
      transparent,
      transparent 12px,
      var(--color-yellow-400) 12px,
      var(--color-yellow-400) 20px
    );
    background-color: var(--color-slate-900);
  }
</style>
