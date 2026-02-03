<script setup lang="ts">
  import type {JobInfo} from '@craftcms/cp/src/types';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {inject, ref, watch} from 'vue';
  import {Axios, Queue} from '@/types/keys';
  import {useFlashMessages} from '@/composables/useFlashMessages';
  import TransitionFade from '@/components/TransitionFade.vue';
  import {useActionClient} from '@/composables/useFetch';
  import {router} from '@inertiajs/vue3';
  import {show} from '@routes/cp/utilities';

  withDefaults(
    defineProps<{
      activeJob?: JobInfo | null;
      jobs?: Array<JobInfo>;
    }>(),
    {jobs: () => [], activeJob: null}
  );

  const queue = inject(Queue);
  const axios = inject(Axios);
  const {
    execute: executeRetryAll,
    status: retryAllStatus,
    error: retryAllError,
  } = useActionClient('queue/retry-all');
  const {
    execute: executeReleaseAll,
    status: releaseAllStatus,
    error: releaseAllError,
  } = useActionClient('queue/release-all');
  const {flash, messages} = useFlashMessages();
  const successDuration = 1000;
  const state = ref({
    retryAll: 'idle',
    releaseAll: 'idle',
  });
  const loading = ref(false);

  function isRetryable(job: JobInfo) {
    return true;
  }

  function clearActiveJob(value: boolean) {}

  function releaseActiveJob() {}

  function retryActiveJob() {}

  async function retryAll() {
    await executeRetryAll();
    flash('success', t('Retrying all failed jobs.'));
    router.visit(show({id: 'queue-manager'}), {
      only: ['contentHtml'],
    });
  }

  watch(retryAllError, () => {
    flash('error', t('Failed to retry all jobs.'));
  });

  watch(releaseAllError, () => {
    flash('error', t('Failed to release all jobs.'));
  });

  async function releaseAll() {
    if (
      !confirm(t('Are you sure you want to release all jobs in the queue?'))
    ) {
      return;
    }

    await executeReleaseAll();
    flash('success', t('All jobs released.'));
    router.visit(show({id: 'queue-manager'}), {
      only: ['contentHtml'],
    });
  }
</script>

<template>
  <template v-if="activeJob">
    <craft-button
      type="button"
      class="btn"
      @click="clearActiveJob(true)"
      data-icon="larr"
      title="{{ 'Back to the queue index'|t('app') }}"
    >
      {{ t('Back') }}
    </craft-button>
    <div class="flex-grow"></div>
    <craft-spinner v-if="loading" class="spinner"></craft-spinner>
    <craft-button
      type="button"
      v-if="isRetryable(activeJob)"
      class="btn"
      data-icon="play"
      @click="retryActiveJob"
    >
      <template v-if="activeJob.status == 2">{{ t('Restart job') }}</template>
      <template v-else>{{ t('Retry') }}</template>
    </craft-button>
    <craft-button
      v-if="activeJob.status != 3"
      class="btn"
      data-icon="remove"
      @click="releaseActiveJob"
    >
      {{ t('Release job') }}
    </craft-button>
  </template>
  <template v-else-if="jobs.length">
    <div class="flex-grow"></div>

    <TransitionFade>
      <template v-if="messages.error">
        <craft-callout
          icon="triangle-exlamation"
          variant="danger"
          appearance="plain"
          >{{ messages.error }}</craft-callout
        >
      </template>
    </TransitionFade>
    <TransitionFade>
      <template v-if="messages.success">
        <craft-callout
          icon="circle-check"
          variant="success"
          appearance="plain"
          >{{ messages.success }}</craft-callout
        >
      </template>
    </TransitionFade>

    <craft-button
      type="button"
      @click="retryAll"
      :loading="retryAllStatus === 'loading'"
    >
      <craft-icon name="play" slot="prefix"></craft-icon>
      {{ t('Retry all failed jobs') }}
    </craft-button>
    <craft-button
      type="button"
      @click="releaseAll"
      :loading="releaseAllStatus === 'loading'"
    >
      <craft-icon name="remove" slot="prefix"></craft-icon>
      {{ t('Release all jobs') }}
    </craft-button>
  </template>
</template>

<style scoped lang="scss"></style>
