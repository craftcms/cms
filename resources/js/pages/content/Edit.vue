<script setup lang="ts">
  import ElementEditPage from '@/modules/elements/components/ElementEditPage.vue';
  import ElementEditScreen from '@/modules/elements/components/ElementEditScreen.vue';
  import {useIsSlideout} from '@/common/composables/screen';

  // Full pages render `ElementEditScreen`, which fills the shell's `main` slot
  // and so owns the whole main region. A slideout panel brings its own header,
  // form and footer, so this stays on the layout-slot editor there.
  //
  // Inline `<AppLayout>` (inside `ElementEditScreen`), so no ambient layout.
  defineOptions({layout: []});

  // The shared edit payload comes from the ElementEditPage pipeline; only the
  // Entry-specific keys (EntryEditViewModel) remain props.
  const props = defineProps<{
    saveId: number | null;
    siteId: number | null;
    entryTypeId: number | null;
    sectionHandle: string | null;
  }>();

  const editor = useIsSlideout() ? ElementEditPage : ElementEditScreen;

  // What `entries/save-entry` needs to resolve the entry it's saving. The
  // field layout and meta fields are collected by the pipeline itself.
  const saveData = () => ({
    entryId: props.saveId,
    siteId: props.siteId,
    typeId: props.entryTypeId,
  });
</script>

<template>
  <component :is="editor" :save-data="saveData" />
</template>
