<script setup lang="ts">
  import {initLegacyUi} from '@/common/craft/legacy-ui';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import {nextTick, useTemplateRef, watch} from 'vue';

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

  const content = useTemplateRef<HTMLElement>('content');
  const footer = useTemplateRef<HTMLElement>('footer');

  /**
   * Starts the legacy UI (form-submit links, menus, …) in a utility's
   * server-rendered markup, which is inert without it. Utility content renders
   * through `DynamicHtmlRenderer`, not `HtmlFragmentRenderer`, so it needs
   * doing here, after `nextTick` so the compiled markup is in the document.
   */
  function bootLegacyUi(element: HTMLElement | null): void {
    if (element) {
      initLegacyUi(element);
    }
  }

  watch(
    () => [props.contentHtml, props.footerHtml],
    async () => {
      await nextTick();
      bootLegacyUi(content.value);
      bootLegacyUi(footer.value);
    },
    {immediate: true}
  );
</script>

<template>
  <LayoutSlot name="content-actions">
    <DynamicHtmlRenderer
      v-if="toolbarHtml"
      :html="toolbarHtml"
    ></DynamicHtmlRenderer>
  </LayoutSlot>
  <CpContainer class="py-lg">
    <div ref="content">
      <DynamicHtmlRenderer v-if="contentHtml" :html="contentHtml" />
    </div>
  </CpContainer>
  <div ref="footer">
    <DynamicHtmlRenderer v-if="footerHtml" :html="footerHtml" />
  </div>
</template>
