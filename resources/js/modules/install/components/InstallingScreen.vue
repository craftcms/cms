<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, nextTick, onMounted, useTemplateRef, watch} from 'vue';
  import {usePost} from '@/common/composables/useFetch';
  import {install as installAction} from '@actions/InstallController';

  type InstallResponse = {
    redirect: string;
  };

  const props = defineProps<{
    data: any;
  }>();

  const {
    execute: install,
    error,
    isSuccess,
    isLoading,
    isError,
  } = usePost<InstallResponse>(installAction().url, {
    onSuccess: ({redirect}) => {
      setTimeout(() => {
        window.location.href = redirect;
      }, 1000);
    },
  });
  const errorMessage = computed(() =>
    error.value instanceof Error ? error.value.message : String(error.value)
  );

  const heading = useTemplateRef<HTMLElement>('heading');

  onMounted(() => {
    install(props.data);
  });
  watch([isLoading, isSuccess, isError], async ([loading, success, error]) => {
    if (loading || success || error) {
      await nextTick();
      heading.value?.focus();
    }
  });
</script>

<template>
  <craft-pane class="max-w-[80ch] mx-auto">
    <template v-if="isLoading">
      <div class="content">
        <h1 ref="heading" tabindex="-1">{{ t('Installing Craft CMS…') }}</h1>
        <craft-spinner></craft-spinner>
      </div>
    </template>

    <template v-else-if="isSuccess">
      <div class="content">
        <h1 ref="heading" tabindex="-1">{{ t('Craft is installed! 🎉') }}</h1>
        <div class="flex justify-center items-center">
          <craft-icon
            name="circle-check"
            variant="regular"
            style="color: var(--c-color-success-fill-loud); font-size: 2.5rem"
          ></craft-icon>
        </div>
      </div>
    </template>

    <template v-else-if="isError">
      <div class="content">
        <h1 ref="heading" tabindex="-1">{{ t('Install failed 😞') }}</h1>

        <div
          class="text-left border border-red-500 rounded p-4 text-red-800 bg-red-50 font-mono text-xs"
        >
          {{ errorMessage }}
        </div>
      </div>
    </template>
  </craft-pane>
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
