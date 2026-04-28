<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {type JobInfo} from '@craftcms/cp/types/index.ts';
  import {useActionClient} from '@/composables/useFetch';
  import {unref, watch} from 'vue';
  import {router} from '@inertiajs/vue3';
  import {show} from '@routes/cp/utilities';
  import {useFlashMessages} from '@/composables/useFlashMessages';

  const props = defineProps<{
    job: JobInfo;
  }>();

  const {flash} = useFlashMessages();
  const {execute, state} = useActionClient('queue/release');

  /**
   * @TODO someday we should replace this with an inertia form
   */
  async function releaseJob() {
    if (
      !confirm(
        t('Are you sure you want to release the job “{description}”?', {
          description: props.job.description,
        })
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
      flash('success', t('Job released.'));
    } else if (newValue === 'error') {
      flash('error', t('Failed to release job.'));
    }
  });
</script>

<template>
  <craft-button
    type="button"
    @click="releaseJob"
    size="small"
    :loading="state === 'loading'"
    v-bind="$attrs"
  >
    <craft-icon name="remove" slot="prefix"></craft-icon>
    {{ t('Release') }}
  </craft-button>
</template>

<style scoped lang="scss"></style>
