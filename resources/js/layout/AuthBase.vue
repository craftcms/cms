<script setup lang="ts">
  import useCraftData from '@/composables/useCraftData';
  import craftCmsLogoUrl from '../../legacy/cp/dist/images/craftcms.svg';

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

      <a
        class="cp-login__powered-by"
        href="http://craftcms.com/"
        title="Powered by Craft CMS"
        aria-label="Craft CMS"
      >
        <img
          :src="craftCmsLogoUrl"
          alt=""
          class="cp-login__craft-logo"
          width="104"
          height="26"
        />
      </a>
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

  .cp-login__powered-by {
    display: block;
    margin-block-start: calc(70rem / 16);
    opacity: 0.8;
    text-align: center;

    &:hover,
    &:focus,
    &:active {
      opacity: 1;
    }
  }

  .cp-login__craft-logo {
    display: inline-block;
    width: calc(104rem / 16);
    height: auto;
  }
</style>
