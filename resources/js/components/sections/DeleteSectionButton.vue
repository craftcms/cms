<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import type {SectionModel} from '@/pages/SettingsSectionsPage.vue';

  const props = defineProps<{
    section: SectionModel;
  }>();

  function handleDelete() {
    if (
      confirm(
        t('Are you sure you want to delete “{name}” and all its entries?', {
          name: props.section.name,
        })
      )
    ) {
      console.log('delete');
    }
  }
</script>

<template>
  <craft-button
    variant="danger"
    type="button"
    size="small"
    icon
    appearance="plain"
    @click="handleDelete"
  >
    <craft-icon :label="t('Delete section')" name="x"></craft-icon>
  </craft-button>
</template>

<style scoped lang="scss"></style>
