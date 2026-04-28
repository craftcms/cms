<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import type {SectionModel} from '@/pages/SettingsSectionsIndexPage.vue';
  import {useForm} from '@inertiajs/vue3';
  import {destroy} from '@actions/Settings/SectionsController';

  const props = defineProps<{
    section: SectionModel;
  }>();

  const form = useForm({
    id: props.section.id,
  });

  function handleDelete() {
    if (
      !confirm(
        t('Are you sure you want to delete “{name}” and all its entries?', {
          name: props.section.name,
        })
      )
    ) {
      return;
    }

    form.submit(destroy());
  }
</script>

<template>
  <form @submit.prevent="handleDelete" method="post">
    <craft-button
      variant="danger"
      type="submit"
      size="small"
      icon
      appearance="plain"
      :loading="form.processing"
    >
      <craft-icon :label="t('Delete section')" name="x"></craft-icon>
    </craft-button>
  </form>
</template>

<style scoped lang="scss"></style>
