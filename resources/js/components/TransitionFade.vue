<script setup lang="ts"></script>

<template>
  <Transition name="fade">
    <slot></slot>
  </Transition>
</template>

<style scoped lang="scss">
  .fade-enter-active,
  .fade-leave-active {
    transition: opacity 0.2s ease;
  }

  .fade-enter-from,
  .fade-leave-to {
    opacity: 0;
  }
</style>
