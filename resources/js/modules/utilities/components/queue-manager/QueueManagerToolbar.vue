<script setup lang="ts">
  import {type JobInfo, JobStatus} from '@/modules/queue/types';
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed} from 'vue';
  import {useMessages} from '@/modules/messages/useMessages';
  import {useForm} from '@inertiajs/vue3';
  import {show} from '@routes/cp/utilities';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import RetryJobButton from '@/modules/utilities/components/queue-manager/RetryJobButton.vue';
  import ReleaseJobButton from '@/modules/utilities/components/queue-manager/ReleaseJobButton.vue';
  import {
    cancelAll,
    retryAll as retryAllAction,
  } from '@actions/QueueController';

  const props = withDefaults(
    defineProps<{
      activeJob?: JobInfo | null;
      jobs?: Array<JobInfo>;
    }>(),
    {jobs: () => [], activeJob: null}
  );

  const messages = useMessages();
  const retryAllForm = useForm({});
  const releaseAllForm = useForm({});

  const isRetryable = computed(() => {
    return (
      props.activeJob?.status.value == JobStatus.Reserved ||
      props.activeJob?.status.value == JobStatus.Failed
    );
  });

  function retryAll() {
    retryAllForm.submit(retryAllAction(), {
      only: ['contentHtml'],
      preserveScroll: true,
      onSuccess: () => {
        messages.success(t('Retrying all failed jobs.'));
      },
      onError: () => {
        messages.error(t('Failed to retry all jobs.'));
      },
    });
  }

  function releaseAll() {
    if (
      !confirm(t('Are you sure you want to release all jobs in the queue?'))
    ) {
      return;
    }

    releaseAllForm.submit(cancelAll(), {
      only: ['contentHtml'],
      preserveScroll: true,
      onSuccess: () => {
        messages.success(t('All jobs released.'));
      },
      onError: () => {
        messages.error(t('Failed to release all jobs.'));
      },
    });
  }
</script>

<template>
  <template v-if="activeJob">
    <CpButtonLink :href="show.url({id: 'queue-manager'})">
      <craft-icon name="arrow-left" slot="prefix"></craft-icon>
      {{ t('Back') }}
    </CpButtonLink>
    <div class="grow"></div>
    <RetryJobButton v-if="isRetryable" :job="activeJob" size="default" />
    <ReleaseJobButton
      :job="activeJob"
      size="default"
      v-if="activeJob.status.value !== JobStatus.Done"
    />
  </template>
  <template v-else-if="jobs.length">
    <craft-button
      type="button"
      @click="retryAll"
      :loading="retryAllForm.processing"
    >
      <craft-icon name="play" slot="prefix"></craft-icon>
      {{ t('Retry all failed jobs') }}
    </craft-button>
    <craft-button
      type="button"
      @click="releaseAll"
      :loading="releaseAllForm.processing"
    >
      <craft-icon name="remove" slot="prefix"></craft-icon>
      {{ t('Release all jobs') }}
    </craft-button>
  </template>
</template>

<style scoped lang="scss"></style>
