<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import dbBackupController from '@actions/Utilities/DbBackupController';
  import {useForm} from '@inertiajs/vue3';
  import type CraftCheckbox from '@craftcms/cp/src/components/checkbox/checkbox';

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
        .checked="form.downloadBackup"
        @model-value-changed="form.downloadBackup = ($event.target as CraftCheckbox)?.checked === true"
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
