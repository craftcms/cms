<script setup lang="ts">
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

  // The utilities nav arrives as the `subnav` page prop and is drawn by the
  // shell's own `SecondaryNav`. Drawing it here meant it existed only as
  // markup, so the nav's collapsed action menu — which is built from the
  // items — had nothing to show below the large breakpoint.
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
  <craft-pane appearance="raised" padding="0" class="@container">
    <div class="content-pane">
      <DynamicHtmlRenderer v-if="contentHtml" :html="contentHtml" />
      <DynamicHtmlRenderer v-if="footerHtml" :html="footerHtml" />
    </div>
  </craft-pane>
</template>
