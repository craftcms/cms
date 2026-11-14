<script setup lang="ts">
  /**
   * The Compare screen. Like Live Preview, it takes over the whole window until
   * it's closed.
   */
  import {router} from '@inertiajs/vue3';
  import {ButtonVariant, t} from '@craftcms/ui';
  import {useTemplateRef} from 'vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import CompareView from '@/modules/elements/components/CompareView.vue';
  import type {
    CompareRow,
    CompareSide,
    CompareSidePayload,
  } from '@/modules/elements/compareLayout';
  import {useFullScreenPanel} from '@/modules/elements/composables/useFullScreenPanel';

  const props = defineProps<{
    from: CompareSidePayload;
    to: CompareSidePayload;
    rows: CompareRow[];
    /** The sides that badge their changed fields. */
    badged: Record<CompareSide, boolean>;
    editUrl: string | null;
    /** The element's chip, as server-rendered HTML, for the heading. */
    elementChip: string;
    /** The same comparison with the two sides swapped. */
    reverseUrl: string | null;
    title: string;
  }>();

  const root = useTemplateRef<HTMLElement>('root');

  useFullScreenPanel(root, () => {
    if (props.editUrl) {
      router.visit(props.editUrl);
    }
  });
</script>

<template>
  <div ref="root" class="compare-page">
    <LayoutSlot name="title">
      <h1
        v-if="elementChip"
        class="compare-page__heading text-xl"
        v-html="t('Compare {element}', {element: elementChip})"
      ></h1>
      <h1 v-else class="text-xl">{{ title }}</h1>
    </LayoutSlot>

    <LayoutSlot v-if="editUrl" name="content-actions">
      <CpButtonLink
        id="compare-close"
        :href="editUrl"
        icon=""
        size="small"
        flush="inline-end"
        :variant="ButtonVariant.Plain"
      >
        <craft-icon name="xmark-large" :label="t('Close')"></craft-icon>
      </CpButtonLink>
      <craft-tooltip for="compare-close">{{ t('Close') }}</craft-tooltip>
    </LayoutSlot>

    <CompareView
      :from="from"
      :to="to"
      :rows="rows"
      :badged="badged"
      :reverse-url="reverseUrl"
    />
  </div>
</template>

<style scoped>
  .compare-page {
    display: flex;
    flex: 1 0 auto;
    flex-direction: column;
  }

  .compare-page__heading :deep(.inline-chips) {
    display: inline-flex;
    vertical-align: middle;
  }
</style>

<style>
  /* The page's panel is the page shell's, so it's reached from here. */
  html:has(.compare-page) {
    overflow: hidden;
  }

  .cp-body__panel:has(.compare-page) {
    position: fixed;
    inset: 0;
    z-index: var(--c-layer-overlay);
    margin: 0;
    border: 0;
    border-radius: 0;
    overflow-y: auto;
    overscroll-behavior: contain;
  }

  .cp-content__main:has(.compare-page) {
    container: compare-page / inline-size;
    display: flex;
    flex-direction: column;
  }

  .cp-content__main > .cp-content-view:has(.compare-page) {
    display: flex;
    flex: 1 0 auto;
    flex-direction: column;
  }
</style>
