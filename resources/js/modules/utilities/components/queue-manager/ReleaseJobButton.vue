<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {type JobInfo} from '@/modules/queue/types';
  import {useForm} from '@inertiajs/vue3';
  import {useFlashMessages} from '@/common/composables/useFlashMessages';
  import {cancel} from '@actions/QueueController';

  const props = defineProps<{
    job: JobInfo;
  }>();

  const {flash} = useFlashMessages();
  const form = useForm({});

  function releaseJob() {
    if (
      !confirm(
        t('Are you sure you want to release the job “{description}”?', {
          description: props.job.description,
        })
      )
    ) {
      return;
    }

    form.submit(cancel({id: props.job.uid}), {
      only: ['contentHtml'],
      preserveScroll: true,
      onSuccess: () => {
        flash('success', t('Job released.'));
      },
      onError: () => {
        flash('error', t('Failed to release job.'));
      },
    });
  }
</script>

<template>
  <craft-button
    type="button"
    @click="releaseJob"
    size="small"
    :loading="form.processing"
    v-bind="$attrs"
  >
    <craft-icon name="remove" slot="prefix"></craft-icon>
    {{ t('Release') }}
  </craft-button>
</template>

<style scoped lang="scss"></style>
