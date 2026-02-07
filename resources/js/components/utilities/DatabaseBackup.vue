<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts';
  import dbBackupController from '@actions/Utilities/DbBackupController';
  import {useForm} from '@inertiajs/vue3';
  // import {downloadFromUrl} from '@craftcms/cp/src/bridge';

  const form = useForm({
    downloadBackup: false,
  });

  function handleSubmit() {
    form.clearErrors();
    form.submit(dbBackupController(), {
      onError: (e) => {
        console.log('uh oh');
      },
    });
  }
</script>

<template>
  <div class="p-4">
    <form @submit.prevent="handleSubmit" id="db-backup" method="post">
      <craft-checkbox
        :label="t('Download backup')"
        name="downloadBackup"
        v-model="form.downloadBackup"
        checked
      ></craft-checkbox>

      <div class="mt-4">
        <craft-button
          type="submit"
          variant="primary"
          :loading="form.processing"
        >
          {{ t('Backup') }}
        </craft-button>
      </div>
    </form>
  </div>
</template>

<style scoped lang="scss"></style>
