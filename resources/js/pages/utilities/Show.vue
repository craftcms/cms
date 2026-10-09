<script setup lang="ts">
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
   * Boots the legacy UI a utility's markup declares.
   *
   * A utility hands over server-rendered HTML — that's what `contentHtml()` is
   * — and the behaviors in it are wired per element rather than delegated:
   * `Craft.initUiElements()` is what calls `$('.formsubmit', $container)
   * .formsubmit()`, and the same for lightswitches, menus and field toggles.
   * Without it a utility's buttons are inert, which is how Shopify's Sync all
   * button came to do nothing at all.
   *
   * `HtmlFragmentRenderer` does this for every other patch of legacy markup in
   * the control panel; utility content renders through `DynamicHtmlRenderer`
   * instead, because core utilities reference Vue components in their HTML, so
   * it needs doing here.
   *
   * After `nextTick`, so the markup the renderer compiles is in the document.
   */
  function bootLegacyUi(element: HTMLElement | null): void {
    if (element) {
      window.Craft?.initUiElements?.(element);
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
