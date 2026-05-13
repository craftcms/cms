<script setup lang="ts">
  import useCraftData from '@/composables/useCraftData';
  import Pane from '@/components/Pane.vue';

  const {general, system} = useCraftData();
</script>

<template>
  <div class="cp-login">
    <div class="grid gap-3">
      <h1 class="flex justify-center" v-if="general.cpLogoUrl">
        <img
          :src="general.cpLogoUrl"
          :alt="system.name"
          width="288px"
          height="auto"
          class="inline-block"
        />
      </h1>

      <div class="w-sm">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .cp-login {
    display: grid;
    width: 100vw;
    height: 100vh;
    place-items: center;
    gap: var(--c-spacing-md);
  }
</style>
