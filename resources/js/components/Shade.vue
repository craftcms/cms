<script setup lang="ts"></script>

<template>
  <div class="shade">
    <slot></slot>
  </div>
</template>

<style scoped lang="scss">
  .shade {
    position: fixed;
    z-index: 10001;
    display: grid;
    place-items: center;
    inset: 0;
    min-width: 100vw;
    min-height: 100vh;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
  }
</style>
