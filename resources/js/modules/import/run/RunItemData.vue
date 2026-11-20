<script setup lang="ts">
  /**
   * A failed item's data, as a slideout panel with room for a long one, shown as a
   * JSON file's preview shows it.
   */
  import {onMounted, useTemplateRef} from 'vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';

  const props = defineProps<{
    /** The data as the server rendered it, with the template a JSON file's preview uses. */
    html: string;
    title: string;
  }>();

  useAppLayout(() => ({title: props.title, hideFooter: true}));

  const preview = useTemplateRef<HTMLElement>('preview');

  onMounted(async () => {
    if (preview.value) {
      // the same highlighting a JSON file's preview gets
      const {highlightCodeBlocks} =
        await import('@/common/utils/highlight-code');
      highlightCodeBlocks(preview.value);
    }
  });
</script>

<template>
  <CpContainer>
    <!-- its data is escaped by the server -->
    <div ref="preview" class="run-item-data" v-html="html"></div>
  </CpContainer>
</template>

<style scoped>
  .run-item-data {
    white-space: normal;
  }

  /* the panel's own padding sets it in, so the preview's side padding would double it */
  .run-item-data :deep(pre) {
    padding-inline: 0 !important;
  }
</style>
