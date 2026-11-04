<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import {useMessages} from '@/modules/messages/useMessages';
  import {useForm} from '@inertiajs/vue3';
  import {
    cancelAll,
    retryAll as retryAllAction,
  } from '@actions/QueueController';

  withDefaults(defineProps<{hasJobs?: boolean}>(), {hasJobs: false});

  const messages = useMessages();
  const retryAllForm = useForm({});
  const releaseAllForm = useForm({});

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
  <template v-if="hasJobs">
    <craft-button
      type="button"
      @click="retryAll"
      .loading="retryAllForm.processing"
    >
      <craft-icon name="play" slot="prefix"></craft-icon>
      {{ t('Retry all failed jobs') }}
    </craft-button>
    <craft-button
      type="button"
      @click="releaseAll"
      .loading="releaseAllForm.processing"
    >
      <craft-icon name="remove" slot="prefix"></craft-icon>
      {{ t('Release all jobs') }}
    </craft-button>
  </template>
</template>

<style scoped lang="scss"></style>
