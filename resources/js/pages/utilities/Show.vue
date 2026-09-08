<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

  // No nav of its own: the utilities hang off the Utilities item in the main
  // navigation, so a utility is a plain page with no sidebar beside it.
  const props = defineProps<{
    id: string;
    title: string;
    contentHtml?: string;
    toolbarHtml?: string;
    footerHtml?: string;
    viewData?: unknown;
  }>();

  useAppLayout(() => ({title: props.title}));
</script>

<template>
  <LayoutSlot name="actions">
    <DynamicHtmlRenderer
      v-if="toolbarHtml"
      :html="toolbarHtml"
    ></DynamicHtmlRenderer>
  </LayoutSlot>
  <div class="py-4">
    <DynamicHtmlRenderer v-if="contentHtml" :html="contentHtml" />
  </div>
  <DynamicHtmlRenderer v-if="footerHtml" :html="footerHtml" />
</template>
