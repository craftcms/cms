<script setup lang="ts">
  import ElementEditor from '@/modules/elements/components/ElementEditor.vue';

  // The shared edit payload comes from the ElementEditor pipeline; only the
  // Entry-specific keys (EntryEditViewModel) remain props.
  const props = defineProps<{
    saveParams: Record<string, number | string | null>;
    entryTypeId: number | null;
    sectionHandle: string | null;
  }>();

  // What the save action needs to resolve the entry it's saving: an entry id
  // for `entries/save-entry`, or a generic element identity for nested entries,
  // which save through `elements/save`. The field layout and meta fields are
  // collected by the pipeline itself.
  const saveData = () => ({...props.saveParams});
</script>

<template>
  <ElementEditor :save-data="saveData" />
</template>
