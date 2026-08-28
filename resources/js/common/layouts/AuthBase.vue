<script setup lang="ts">
  import {Head} from '@inertiajs/vue3';
  import useCraftData from '@/common/composables/useCraftData';
  import craftCmsLogoUrl from '../../../images/craftcms.svg';
  import {t} from '@craftcms/ui';
  import LiveRegion from '@/common/components/LiveRegion.vue';

  const props = withDefaults(
    defineProps<{
      title?: string;
    }>(),
    {
      title: t('Sign In'),
    }
  );

  const {general, system} = useCraftData();
</script>

<template>
  <Head :title="props.title"></Head>
  <main class="cp-login">
    <LiveRegion />
    <div class="cp-login__wrapper cp:grid cp:gap-3 cp:justify-items-center">
      <h1 class="cp:flex cp:justify-center">
        <img
          v-if="general.cpLogoUrl"
          :src="general.cpLogoUrl"
          :alt="system.name"
          class="cp-login__logo"
          width="288px"
        />
        <template v-else>
          {{ system.name }}
        </template>
      </h1>

      <div class="cp-login__form-wrapper">
        <slot></slot>
      </div>

      <a
        class="cp-login__powered-by"
        href="https://craftcms.com/"
        title="Powered by Craft CMS"
      >
        <img
          :src="craftCmsLogoUrl"
          alt="Craft CMS"
          class="cp-login__craft-logo"
          width="104"
          height="26"
        />
      </a>
    </div>
  </main>
</template>

<style scoped lang="scss">
  .cp-login {
    display: grid;
    width: 100vw;
    height: 100vh;
    place-items: center;
    gap: var(--c-spacing-md);
    padding: var(--c-spacing-lg);
  }

  .cp-login__wrapper {
    width: 100%;
    max-width: var(--container-sm);
  }

  .cp-login__logo {
    display: inline-block;
    width: calc(288rem / 16);
    height: auto;
  }

  .cp-login__form-wrapper {
    width: 100%;
  }

  .cp-login__powered-by {
    display: block;
    margin-block-start: calc(70rem / 16);
    opacity: 0.92;
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
