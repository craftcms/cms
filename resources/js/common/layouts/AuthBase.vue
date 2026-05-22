<script setup lang="ts">
  import useCraftData from '@/common/composables/useCraftData';

  const {general, system} = useCraftData();
</script>

<template>
  <div class="cp-login">
    <div class="grid gap-3 justify-items-center">
      <h1 class="flex justify-center" v-if="general.cpLogoUrl">
        <img
          :src="general.cpLogoUrl"
          :alt="system.name"
          class="cp-login__logo"
          width="288px"
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

  .cp-login__logo {
    display: inline-block;
    width: calc(288rem / 16);
    height: auto;
  }
</style>
