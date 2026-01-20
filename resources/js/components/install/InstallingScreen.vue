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
  <Pane class="max-w-[80ch] mx-auto">
    <template v-if="isLoading">
      <div class="content">
        <h2>{{ t('Installing Craft CMS…') }}</h2>
        <craft-spinner></craft-spinner>
      </div>
    </template>

    <template v-else-if="isSuccess">
      <div class="content">
        <h2>{{ t('Craft is installed! 🎉') }}</h2>
        <div class="flex justify-center items-center">
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
        <h2>{{ t('Install failed 😞') }}</h2>

        <div
          class="text-left border border-red-500 rounded p-4 text-red-800 bg-red-50 font-mono text-xs"
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
