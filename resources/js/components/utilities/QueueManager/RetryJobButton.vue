<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {type JobInfo, JobStatus} from '@craftcms/cp/src/types/index.js';
  import {useActionClient} from '@/composables/useFetch';
  import {unref, watch} from 'vue';
  import {router} from '@inertiajs/vue3';
  import {show} from '@routes/cp/utilities';
  import {useFlashMessages} from '@/composables/useFlashMessages';

  const props = defineProps<{
    job: JobInfo;
  }>();

  const {flash} = useFlashMessages();
  const {execute, state} = useActionClient('queue/retry');

  async function retryJob() {
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

    await execute({id: unref(props.job.uid)});
    router.visit(show({id: 'queue-manager'}), {
      only: ['contentHtml'],
    });
  }

  watch(state, (newValue) => {
    if (newValue === 'success') {
      if (props.job.status.value === JobStatus.Reserved) {
        flash('success', t('Job restarted.'));
      } else {
        flash('success', t('Job retried.'));
      }
    } else if (newValue === 'error') {
      flash('error', t('Failed to retry job.'));
    }
  });
</script>

<template>
  <craft-button
    type="button"
    @click="retryJob"
    size="small"
    :loading="state === 'loading'"
    v-bind="$attrs"
  >
    <craft-icon name="play" slot="prefix" style="font-size: 0.7em"></craft-icon>
    {{ t('Retry') }}
  </craft-button>
</template>

<style scoped lang="scss"></style>
