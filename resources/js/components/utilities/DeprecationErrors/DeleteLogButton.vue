<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {useForm} from '@inertiajs/vue3';
  import {destroy} from '@actions/Utilities/DeprecationErrorsController';

  const props = defineProps<{
    logId: number;
  }>();

  const form = useForm({
    logId: props.logId,
  });

  async function deleteLog() {
    form.submit(destroy(props.logId), {
      preserveScroll: true,
      preserveState: true,
    });
  }
</script>

<template>
  <craft-button
    size="small"
    icon
    appearance="plain"
    @click="deleteLog"
    :loading="form.processing"
  >
    <craft-icon name="remove" :label="t('Delete log')"></craft-icon>
  </craft-button>
</template>

<style scoped lang="scss"></style>
