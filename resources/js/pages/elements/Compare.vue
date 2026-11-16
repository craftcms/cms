<script setup lang="ts">
  /**
   * The Compare screen. Like Live Preview, it takes over the whole window until
   * it's closed.
   */
  import {router} from '@inertiajs/vue3';
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed, useTemplateRef} from 'vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
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
    /**
     * The sides that badge their changed fields: the one that made the
     * changes, when one did, and otherwise both.
     */
    badged: Record<CompareSide, boolean>;
    /**
     * Each side's fields' UIDs in the other side's layout, where they differ,
     * which pair a field a condition newly shows with the other side's.
     */
    counterparts: Record<CompareSide, Record<string, string>>;
    /** Where closing the screen goes. */
    closeUrl: string | null;
    /** The element's chip, as server-rendered HTML, for the heading: B's, of two elements. */
    elementChip: string;
    /** A's chip, when the screen compares two separate elements. */
    fromElementChip?: string | null;
    /** The same comparison with the two sides swapped. */
    reverseUrl: string | null;
    title: string;
  }>();

  const root = useTemplateRef<HTMLElement>('root');
  const view = useTemplateRef<InstanceType<typeof CompareView>>('view');

  /**
   * The heading's HTML, when there's a chip to show, with each chip kept on a
   * line with the word before it ("Compare", "with"), so a line only breaks
   * between the two pairs. A chip with no word before it in the translation
   * is left as it is.
   */
  const headingHtml = computed<string | null>(() => {
    if (!props.elementChip) {
      return null;
    }

    const chips: Record<string, string> = props.fromElementChip
      ? {from: props.fromElementChip, to: props.elementChip}
      : {element: props.elementChip};
    const markers = Object.fromEntries(
      Object.keys(chips).map((key) => [key, `${key}`])
    );
    const message = props.fromElementChip
      ? t('Compare {from} with {to}', markers)
      : t('Compare {element}', markers);

    return message.replace(
      /(?:([^\s\uE000]+)\s+)?(\w+)/g,
      (_match, word: string | undefined, key: string) =>
        word
          ? `<span class="compare-page__pair">${word} ${chips[key]}</span>`
          : chips[key]!
    );
  });

  // The page's form submits for the side being edited, and each side's own
  // footer, rather than the page's, holds its Save button.
  useAppLayout(() => ({
    form: null,
    ...view.value?.appLayout(),
    handlesReadOnly: true,
    hideFooter: true,
  }));

  useFullScreenPanel(root, () => {
    if (props.closeUrl) {
      router.visit(props.closeUrl);
    }
  });
</script>

<template>
  <div ref="root" class="compare-page">
    <LayoutSlot name="title">
      <div class="compare-page__title">
        <h1
          v-if="headingHtml"
          class="compare-page__heading text-xl"
          v-html="headingHtml"
        ></h1>
        <h1 v-else class="compare-page__heading cp-h1">{{ title }}</h1>

        <div v-if="closeUrl" class="compare-page__close text-xl">
          <CpButtonLink
            id="compare-close"
            :href="closeUrl"
            icon=""
            size="small"
            flush="inline-end"
            :variant="ButtonVariant.Plain"
          >
            <craft-icon name="xmark-large" :label="t('Close')"></craft-icon>
          </CpButtonLink>
          <craft-tooltip for="compare-close">{{ t('Close') }}</craft-tooltip>
        </div>
      </div>
    </LayoutSlot>

    <CompareView
      ref="view"
      :from="from"
      :to="to"
      :rows="rows"
      :badged="badged"
      :counterparts="counterparts"
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

  .compare-page__title {
    display: flex;
    flex: 1 1 0;
    gap: var(--c-spacing-md);
    align-items: first baseline;
    min-width: 0;
  }

  .compare-page__heading {
    flex: 1 1 0;
    min-width: 0;
  }

  /* A line of the heading's own type, so the button centers on its first
     line the way the chips do. */
  .compare-page__close {
    flex: none;
  }

  .compare-page__close :deep(craft-button) {
    font-size: var(--c-text-base);
    vertical-align: middle;
  }

  .compare-page__heading :deep(.compare-page__pair) {
    display: inline-flex;
    column-gap: 0.25em;
    align-items: center;
    max-width: 100%;
  }

  .compare-page__heading :deep(.inline-chips) {
    display: inline-flex;
    min-width: 0;
    max-width: 100%;
    margin-block: 1px;
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
