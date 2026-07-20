<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {useFlashMessages} from '@/common/composables/useFlashMessages';
  import {useForm} from '@inertiajs/vue3';
  import {deleteDeprecationError} from '@actions/Utilities/DeprecationErrorsController';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';

  const {flash} = useFlashMessages();

  const props = defineProps<{
    logId: number;
  }>();

  const form = useForm({
    logId: props.logId,
  });

  async function deleteLog() {
    form.submit(deleteDeprecationError(), {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        flash('success', t('Log deleted.'));
      },
      onError: () => {
        flash('error', t('Failed to delete log.'));
      },
    });
  }
</script>

<template>
  <DeleteButton
    :loading="form.processing"
    @click="deleteLog"
    :label="t('Delete log')"
  />
</template>

<style scoped lang="scss"></style>
