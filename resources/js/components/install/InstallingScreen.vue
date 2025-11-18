<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {defineProps, onMounted} from 'vue';
  import {usePost} from '@/composables/useFetch';
  import {usePage} from '@inertiajs/vue3';
  import Pane from '@/components/Pane.vue';

  const {props: pageProps} = usePage();

  const props = defineProps<{
    data: any;
  }>();

  const {
    execute: install,
    error,
    isSuccess,
    isLoading,
    isError,
  } = usePost('/admin/actions/install/install', {
    onSuccess: (data) => {
      setTimeout(() => {
        window.location.href = pageProps.postCpLoginRedirect as string;
      }, 1000);
    },
  });

  onMounted(async () => {
    await install(props.data);
  });
</script>

<template>
  <Pane class="tw:max-w-[80ch] tw:mx-auto">
    <template v-if="isLoading">
      <div class="content">
        <h2>{{ t('app', 'Installing Craft CMS…') }}</h2>
        <craft-spinner></craft-spinner>
      </div>
    </template>

    <template v-else-if="isSuccess">
      <div class="content">
        <h2>{{ t('app', 'Craft is installed! 🎉') }}</h2>
        <div class="tw:flex tw:justify-center tw:items-center">
          <craft-icon
            name="circle-check"
            variant="regular"
            style="color: var(--c-color-success-bg-emphasis); font-size: 2.5rem"
          ></craft-icon>
        </div>
      </div>
    </template>

    <template v-if="isError">
      <div class="content">
        <h2>{{ t('app', 'Install failed 😞') }}</h2>

        <div
          class="tw:text-left tw:border tw:border-red-500 tw:rounded tw:p-4 tw:text-red-800 tw:bg-red-50 tw:font-mono tw:text-xs"
        >
          {{ error.message }}
        </div>
      </div>
    </template>
  </Pane>
</template>

<style scoped lang="scss">
  .content {
    padding: var(--c-spacing-lg);
    display: grid;
    justify-content: center;
    align-items: center;
    gap: var(--c-spacing-lg);
    text-align: center;
  }
</style>
