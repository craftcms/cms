<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {defineProps, onMounted} from 'vue';
  import {usePost} from '@/composables/useFetch';
  import {usePage} from '@inertiajs/vue3';

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
  <template v-if="isLoading">
    <div class="tw:p-8 tw:grid tw:justify-center tw:content-center tw:gap-4">
      <h2>{{ t('app', 'Installing Craft CMS…') }}</h2>
      <craft-spinner></craft-spinner>
    </div>
  </template>

  <template v-else-if="isSuccess">
    <div class="tw:p-8 tw:grid tw:justify-center tw:content-center tw:gap-4">
      <h2>{{ t('app', 'Craft is installed! 🎉') }}</h2>
      <craft-icon name="circle-check"></craft-icon>
    </div>
  </template>

  <template v-if="isError">
    <div class="tw:p-8 tw:grid tw:justify-center tw:content-center tw:gap-4">
      <h2 class="tw:text-center">{{ t('app', 'Install failed 😞') }}</h2>

      <div
        class="tw:border tw:border-red-500 tw:rounded tw:p-4 tw:text-red-800 tw:bg-red-50 tw:font-mono tw:text-xs"
      >
        {{ error.message }}
      </div>
    </div>
  </template>
</template>

<style scoped lang="scss"></style>
