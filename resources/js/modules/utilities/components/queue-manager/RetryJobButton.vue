<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {type JobInfo, JobStatus} from '@/modules/queue/types';
  import {useForm} from '@inertiajs/vue3';
  import {useFlashMessages} from '@/common/composables/useFlashMessages';
  import {retry} from '@actions/QueueController';

  const props = defineProps<{
    job: JobInfo;
  }>();

  const {flash} = useFlashMessages();
  const form = useForm({});

  function retryJob() {
    if (
      !confirm(
        t(
          'Are you sure you want to restart the job “{description}”? Any progress could be lost.',
          {
            description: props.job.description,
          }
        )
      )
    ) {
      return;
    }

    form.submit(retry({id: props.job.uid}), {
      only: ['contentHtml'],
      preserveScroll: true,
      onSuccess: () => {
        if (props.job.status.value === JobStatus.Reserved) {
          flash('success', t('Job restarted.'));
        } else {
          flash('success', t('Job retried.'));
        }
      },
      onError: () => {
        flash('error', t('Failed to retry job.'));
      },
    });
  }
</script>

<template>
  <craft-button
    type="button"
    @click="retryJob"
    size="small"
    :loading="form.processing"
    v-bind="$attrs"
  >
    <craft-icon name="play" slot="prefix" style="font-size: 0.7em"></craft-icon>
    {{ t('Retry') }}
  </craft-button>
</template>

<style scoped lang="scss"></style>
