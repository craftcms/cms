<script setup lang="ts">
  import {t} from '@craftcms/ui/utilities/translate';
  import dbBackupController from '@actions/Utilities/DbBackupController';
  import {useForm} from '@inertiajs/vue3';
  import CraftCheckbox from '@craftcms/ui/vue/CraftCheckbox.vue';
  import {useTemplateRef} from 'vue';
  import useCraftData from '@/common/composables/useCraftData';

  const form = useForm({
    downloadBackup: true,
  });

  const {csrfTokenValue, csrfTokenName} = useCraftData();
  const formRef = useTemplateRef('formRef');

  function handleSubmit() {
    form.clearErrors();

    // If downloading, submit form natively to handle file response
    if (form.downloadBackup) {
      formRef.value?.submit();
      return;
    }

    form.post(dbBackupController().url, {
      onSuccess: () => {
        form.reset();
      },
    });
  }
</script>

<template>
  <div class="cp:p-4">
    <form
      :action="dbBackupController().url"
      ref="formRef"
      @submit.prevent="handleSubmit"
      id="db-backup"
      method="post"
    >
      <input
        v-if="csrfTokenName && csrfTokenValue"
        type="hidden"
        :name="csrfTokenName"
        :value="csrfTokenValue"
      />
      <CraftCheckbox
        :label="t('Download backup')"
        name="downloadBackup"
        v-model="form.downloadBackup"
        value="on"
      />

      <div class="cp:mt-4">
        <craft-button type="submit" variant="accent" :loading="form.processing">
          {{ t('Backup') }}
        </craft-button>
      </div>
    </form>
  </div>
</template>

<style scoped lang="scss"></style>
