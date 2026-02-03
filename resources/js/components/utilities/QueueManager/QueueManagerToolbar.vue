<script setup lang="ts">
  import {type JobInfo, JobStatus} from '@craftcms/cp/src/types/index.js';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {computed, inject, ref, watch} from 'vue';
  import {Axios, Queue} from '@/types/keys';
  import {useFlashMessages} from '@/composables/useFlashMessages';
  import TransitionFade from '@/components/TransitionFade.vue';
  import {useActionClient} from '@/composables/useFetch';
  import {router} from '@inertiajs/vue3';
  import {show} from '@routes/cp/utilities';
  import CpLink from '@/components/CpLink.vue';
  import RetryJobButton from '@/components/utilities/QueueManager/RetryJobButton.vue';
  import ReleaseJobButton from '@/components/utilities/QueueManager/ReleaseJobButton.vue';

  const props = withDefaults(
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
    state: retryAllStatus,
    error: retryAllError,
  } = useActionClient('queue/retry-all');
  const {
    execute: executeReleaseAll,
    state: releaseAllStatus,
    error: releaseAllError,
  } = useActionClient('queue/release-all');
  const {flash, messages} = useFlashMessages();
  const successDuration = 1000;
  const state = ref({
    retryAll: 'idle',
    releaseAll: 'idle',
  });
  const loading = ref(false);

  const isRetryable = computed(() => {
    return (
      props.activeJob.status.value == JobStatus.Reserved ||
      props.activeJob.status.value == JobStatus.Failed
    );
  });

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
    <CpLink as="craft-button" :href="show.url({id: 'queue-manager'})">
      <craft-icon name="arrow-left" slot="prefix"></craft-icon>
      {{ t('Back') }}
    </CpLink>
    <div class="grow"></div>
    <craft-spinner v-if="loading" class="spinner"></craft-spinner>
    <RetryJobButton v-if="isRetryable" :job="activeJob" size="default" />
    <ReleaseJobButton
      :job="activeJob"
      size="default"
      v-if="activeJob.status.value !== JobStatus.Done"
    />
  </template>
  <template v-else-if="jobs.length">
    <div class="grow"></div>

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
