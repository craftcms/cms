<script setup lang="ts">
  /**
   * How running a step would change one of its items' elements, as the
   * Compare screen shows two versions of one, with arrows (on screen and on
   * the keyboard) and an item number to move through the step's other items.
   */
  import '@craftcms/ui/components/badge/badge';
  import '@craftcms/ui/components/button/button';
  import '@craftcms/ui/components/callout/callout';
  import '@craftcms/ui/components/icon/icon';
  import '@craftcms/ui/components/pane/pane';
  import '@craftcms/ui/components/spinner/spinner';
  import {isHttpError, t} from '@craftcms/ui';
  import {useEventListener} from '@vueuse/core';
  import {computed, shallowReactive, watch} from 'vue';
  import Modal from '@/common/components/Modal.vue';
  import PaginationControls from '@/common/components/PaginationControls.vue';
  import CompareView from '@/modules/elements/components/CompareView.vue';
  import type {ImportStep} from '@/modules/import/mapping/types';
  import {
    fetchItemPreview,
    resultFills,
    resultLabels,
    type ItemPreview,
    type PreviewItem,
  } from './plan-preview';

  const {step, items} = defineProps<{
    step: ImportStep;
    /** The items it moves through, in order. */
    items: PreviewItem[];
  }>();

  /** The shown item's position in `items`, or `null` while it's closed. */
  const position = defineModel<number | null>({required: true});

  const item = computed(() =>
    position.value === null ? null : (items[position.value] ?? null)
  );

  /** Each item's preview once it's loaded, by its index in the step's data. */
  const previews = shallowReactive(new Map<number, ItemPreview>());
  const preview = computed(() =>
    item.value ? previews.get(item.value.index) : undefined
  );

  // the step's mapping is the same as long as the modal is open, but not from one opening to the next
  watch(
    () => position.value === null,
    (closed) => closed && previews.clear()
  );

  watch(
    item,
    async (current) => {
      if (!current || previews.has(current.index)) {
        return;
      }

      try {
        previews.set(
          current.index,
          await fetchItemPreview(step, current.index)
        );
      } catch (error) {
        previews.set(current.index, {
          error:
            isHttpError(error) && error.message
              ? error.message
              : t('The item couldn’t be previewed.'),
        });
      }
    },
    {immediate: true}
  );

  /** Whether a key press there is typing, which the arrow keys move the caret in. */
  function isTyping(event: KeyboardEvent): boolean {
    const target = event.composedPath()[0];

    return (
      target instanceof HTMLElement &&
      (target.isContentEditable ||
        ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))
    );
  }

  useEventListener(document, 'keydown', (event: KeyboardEvent) => {
    if (
      position.value === null ||
      event.defaultPrevented ||
      event.altKey ||
      event.ctrlKey ||
      event.metaKey ||
      event.shiftKey ||
      isTyping(event)
    ) {
      return;
    }

    const step =
      event.key === 'ArrowRight' ? 1 : event.key === 'ArrowLeft' ? -1 : 0;
    // in a right-to-left language the next item is to the left
    const rtl = document.documentElement.dir === 'rtl';
    const next = position.value + (rtl ? -step : step);

    if (step === 0 || next < 0 || next >= items.length) {
      return;
    }

    event.preventDefault();
    position.value = next;
  });

  const title = computed(
    () => preview.value?.title || item.value?.title || t('Untitled')
  );
  const result = computed(() => preview.value?.result ?? item.value?.result);
</script>

<template>
  <Modal
    :is-active="position !== null"
    :label="title"
    width="5xl"
    height="100vh"
    resizable
    @close="position = null"
  >
    <craft-pane class="item-preview">
      <div slot="title" class="item-preview__title">
        <h2 class="m-0 text-lg">{{ title }}</h2>
        <craft-badge
          v-if="result"
          size="small"
          :fill="resultFills[result]"
          no-prefix
          >{{ resultLabels[result] }}</craft-badge
        >
      </div>
      <div slot="header-actions" class="item-preview__nav">
        <PaginationControls
          v-if="position !== null"
          controls-only
          :page-index="position"
          :page-size="1"
          :page-count="items.length"
          :page-label="t('Item')"
          :current-page-label="t('Current item')"
          :previous-page-label="t('Previous item')"
          :next-page-label="t('Next item')"
          @page-change="position = $event"
        />
        <craft-button
          type="button"
          icon="xmark-large"
          :aria-label="t('Close')"
          variant="plain"
          size="small"
          flush="inline-end"
          @click="position = null"
        ></craft-button>
      </div>

      <div slot="body" class="item-preview__body">
        <div v-if="!preview" class="item-preview__message">
          <craft-spinner visible :label="t('Loading')"></craft-spinner>
        </div>
        <template v-else>
          <div
            v-if="preview.error || preview.errors?.length"
            class="item-preview__problems"
          >
            <craft-callout variant="danger" role="alert">
              <craft-icon
                slot="icon"
                name="triangle-exclamation"
                data-color="danger"
                aria-hidden="true"
              ></craft-icon>
              <p v-if="preview.error" class="m-0">{{ preview.error }}</p>
              <ul v-if="preview.errors?.length" class="m-0">
                <li v-for="message in preview.errors" :key="message">
                  {{ message }}
                </li>
              </ul>
            </craft-callout>
          </div>
          <p
            v-if="preview.result === 'skip'"
            class="item-preview__message text-quiet"
          >
            {{ t('The plan leaves this item out, so nothing changes.') }}
          </p>
          <CompareView
            v-if="preview.comparison"
            :key="item?.index"
            v-bind="preview.comparison"
          />
        </template>
      </div>
      <p slot="footer-content" class="m-0 text-sm text-quiet">
        {{ t('Changed fields are highlighted. Nothing is saved.') }}
      </p>
    </craft-pane>
  </Modal>
</template>

<style scoped>
  .item-preview {
    flex: 1;
    min-height: 0;
    --c-pane-max-block-size: 100%;
  }

  /* the pane clips what overflows it, so the body scrolls between the header and footer instead */
  .item-preview::part(base) {
    display: flex;
    flex-direction: column;
  }

  .item-preview__title {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    min-width: 0;
  }

  .item-preview__nav {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-xs);
  }

  .item-preview__body {
    display: flex;
    flex: 1;
    flex-direction: column;
    min-height: 0;
    overflow-y: auto;
  }

  .item-preview__message {
    display: flex;
    justify-content: center;
    padding: var(--c-spacing-xl);
  }

  .item-preview__problems {
    padding: var(--c-spacing-md);
  }
</style>
