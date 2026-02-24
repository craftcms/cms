<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts';
  import {useFlashMessages} from '@/composables/useFlashMessages';
  import {useActionClient} from '@/composables/useFetch';
  import {watch} from 'vue';
  import {router} from '@inertiajs/vue3';
  import {deleteDeprecationError} from '@actions/Utilities/DeprecationErrorsController';

  const {flash} = useFlashMessages();
  const {execute, state} = useActionClient(
    'utilities/delete-deprecation-error'
  );

  const props = defineProps<{
    logId: number;
  }>();

  async function deleteLog() {
    await execute({logId: props.logId});
    flash('success', t('Log deleted.'));
    router.visit(deleteDeprecationError());
  }

  watch(state, (newValue) => {
    if (newValue === 'success') {
      flash('success', t('Log deleted.'));
    } else if (newValue === 'error') {
      flash('error', t('Failed to delete log.'));
    }
  });
</script>

<template>
  <craft-button
    size="small"
    icon
    appearance="plain"
    @click="deleteLog"
    :loading="state === 'loading'"
  >
    <craft-icon name="remove" :label="t('Delete log')"></craft-icon>
  </craft-button>
</template>

<style scoped lang="scss"></style>
