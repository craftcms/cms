<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {useForm} from '@inertiajs/vue3';
  import {destroy} from '@actions/Utilities/DeprecationErrorsController';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';

  const props = defineProps<{
    logId: number;
  }>();

  const form = useForm({});

  async function deleteLog() {
    // The server flashes the outcome, which shows as a notification.
    form.submit(destroy({logId: props.logId}), {
      preserveScroll: true,
      preserveState: true,
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
