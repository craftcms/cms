<script setup lang="ts">
  import ElementEditor from '@/modules/elements/components/ElementEditor.vue';

  // The shared edit payload comes from the ElementEditor pipeline; only the
  // Entry-specific keys (EntryEditViewModel) remain props.
  const props = defineProps<{
    saveId: number | null;
    siteId: number | null;
    entryTypeId: number | null;
    sectionHandle: string | null;
  }>();

  // What `entries/save-entry` needs to resolve the entry it's saving. The
  // field layout and meta fields are collected by the pipeline itself.
  const saveData = () => ({
    entryId: props.saveId,
    siteId: props.siteId,
    typeId: props.entryTypeId,
  });
</script>

<template>
  <ElementEditor :save-data="saveData" />
</template>
