<script setup lang="ts">
  /**
   * Compares an image with another, or with each of several in turn, chosen
   * from a menu in place of the second one's heading. Side by side, each image is centered in a box the same size as
   * the other's, above a row for each detail, which is filled across both
   * sides where they differ. With the slider, the two are stacked in one
   * larger box, and a divider between them slides to show more of either.
   */
  import {t} from '@craftcms/ui';
  import {
    computed,
    nextTick,
    onBeforeUnmount,
    reactive,
    shallowRef,
    useId,
    watch,
  } from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {
    ComparedDetail,
    ComparedImage,
    ImageComparisonView,
  } from '@/common/components/imageComparison';
  import type {ActionItems} from '@/common/types';

  const props = withDefaults(
    defineProps<{
      from: ComparedImage;
      /** The images to compare it with. With more than one, a menu switches between them. */
      to: ComparedImage[];
      fromLabel: string;
      toLabel: string;
      details: ComparedDetail[];
      /**
       * Whether the column headings are only for screen readers, for a host
       * that already shows which side is which, such as the Compare screen.
       * Hidden, they have no menu or links.
       */
      hideHeadings?: boolean;
      /**
       * Whether it says which details differ, once it's shown and whenever
       * that changes. Off while it's out of sight, such as in a dialog
       * that hasn't opened yet, where it wouldn't be heard.
       */
      announce?: boolean;
    }>(),
    {hideHeadings: false, announce: true}
  );

  /** The index of the image in `to` being shown. */
  const selected = defineModel<number>('selected', {default: 0});
  /** The view, which `ImageComparisonViews` switches, once `slidable` is true. */
  const view = defineModel<ImageComparisonView>('view', {
    default: 'sideBySide',
  });

  const id = useId();
  const image = computed(() => props.to[selected.value] ?? props.to[0]!);

  /** The previews that couldn't be shown, by key, `from` included. */
  const failed = reactive(new Set<string | number>());

  function src(compared: ComparedImage): string | null {
    return failed.has(compared.key) ? null : compared.src;
  }

  /**
   * Whether the slider can be offered, which takes a preview of every image.
   * Exposed for the host, which shows the views' buttons when it's true.
   */
  const slidable = computed(
    () =>
      Boolean(src(props.from)) &&
      props.to.every((compared) => Boolean(src(compared)))
  );

  const shownView = computed<ImageComparisonView>(() =>
    slidable.value ? view.value : 'sideBySide'
  );

  /**
   * The first image's proportions, once its preview loads, which the other
   * images are fitted to, so the first fills its box and copies of it do too.
   */
  const ratio = shallowRef(3 / 2);
  const aspectRatio = computed(() => String(ratio.value));

  function measure(event: Event): void {
    const image = event.target as HTMLImageElement;
    if (image.naturalWidth && image.naturalHeight) {
      ratio.value = image.naturalWidth / image.naturalHeight;
    }
  }

  /** How far the slider's divider is across the box, from the inline start, as a percentage. */
  const position = shallowRef(50);

  function value(compared: ComparedImage, detail: ComparedDetail) {
    return compared.details[detail.key] ?? null;
  }

  function differs(compared: ComparedImage, detail: ComparedDetail): boolean {
    const a = value(props.from, detail);
    const b = value(compared, detail);

    return detail.compared !== false && a !== null && b !== null && a !== b;
  }

  /** Which details differ for the image being shown. */
  const differences = computed(() => {
    const labels = props.details
      .filter((detail) => differs(image.value, detail))
      .map((detail) => detail.label);

    return labels.length
      ? t('These details differ: {details}', {details: labels.join(', ')})
      : t('None of these details differ.');
  });

  /**
   * Says which details differ, as `announce` asks, and again for each image
   * shown, even when the same ones differ. This has its own live
   * region, as a dialog makes the page's announcer inert, and clears it once
   * it's been read.
   */
  const status = shallowRef('');
  let clearStatus: ReturnType<typeof setTimeout> | undefined;

  watch(
    [() => props.announce, differences, selected],
    async ([announce]) => {
      if (!announce) {
        return;
      }
      clearTimeout(clearStatus);
      // Emptied first, so the same message is read again.
      status.value = '';
      await nextTick();
      status.value = differences.value;
      clearStatus = setTimeout(() => (status.value = ''), 5000);
    },
    {immediate: true}
  );

  onBeforeUnmount(() => clearTimeout(clearStatus));

  /** The images to compare with, to choose from, when there's more than one. */
  const choices = computed<ActionItems>(() =>
    props.to.map((compared, index) => ({
      label: compared.title,
      selected: index === selected.value,
      onClick: () => (selected.value = index),
    }))
  );

  const headerId = (part: string) => `${id}-${part}`;

  function shown(side: 'from' | 'to'): ComparedImage {
    return side === 'from' ? props.from : image.value;
  }

  defineExpose({slidable});
</script>

<template>
  <div class="image-comparison" data-image-comparison>
    <p class="sr-only" role="status">{{ status }}</p>
    <table
      class="image-comparison__table"
      :class="{'image-comparison__table--hidden-headings': hideHeadings}"
    >
      <caption class="sr-only">
        {{
          t('{from} compared with {to}', {
            from: from.title,
            to: image.title,
          })
        }}
      </caption>
      <thead>
        <tr>
          <th
            v-for="side in ['from', 'to'] as const"
            :key="side"
            :id="headerId(side)"
            scope="col"
          >
            <span v-if="hideHeadings" class="sr-only">{{
              side === 'from' ? fromLabel : toLabel
            }}</span>
            <div v-else class="image-comparison__heading">
              <!-- Keyed on the image, as the menu renders its button once. -->
              <ActionMenu
                v-if="side === 'to' && to.length > 1"
                :key="image.key"
                :label="toLabel"
                :actions="choices"
              >
                <template #invoker>
                  <craft-button
                    slot="invoker"
                    class="image-comparison__choose"
                    type="button"
                    variant="plain"
                    size="small"
                    flush="inline-start"
                  >
                    {{ image.title }}
                    <span class="sr-only">{{ t('Choose an image') }}</span>
                    <craft-icon slot="suffix" name="chevron-down"></craft-icon>
                  </craft-button>
                </template>
              </ActionMenu>
              <span v-else>{{ side === 'from' ? fromLabel : toLabel }}</span>
              <template v-if="shown(side).url">
                <!-- A link's name goes on the host, which passes it to the
                     link in its shadow root. -->
                <craft-button
                  :id="headerId(`${side}-link`)"
                  :key="shown(side).key"
                  class="image-comparison__link"
                  :href="shown(side).url"
                  target="_blank"
                  rel="noopener"
                  variant="plain"
                  size="small"
                  flush="inline-end"
                  icon="arrow-up-right-from-square"
                  :aria-label="
                    t('{title} (opens in a new window)', {
                      title: shown(side).title,
                    })
                  "
                ></craft-button>
                <craft-tooltip
                  :key="`${shown(side).key}-tooltip`"
                  :for="headerId(`${side}-link`)"
                  >{{ shown(side).title }}</craft-tooltip
                >
              </template>
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="shownView === 'slider'">
          <td
            colspan="2"
            class="image-comparison__slider-cell"
            :headers="`${headerId('from')} ${headerId('to')}`"
          >
            <div
              class="image-comparison__slider"
              :style="{'--position': `${position}%`, '--ratio': ratio}"
            >
              <img
                class="image-comparison__slider-image"
                :src="src(image)!"
                :alt="image.title"
                @error="failed.add(image.key)"
              />
              <img
                class="image-comparison__slider-image image-comparison__slider-image--from"
                :src="src(from)!"
                :alt="from.title"
                @load="measure"
                @error="failed.add(from.key)"
              />
              <span
                class="image-comparison__slider-tag image-comparison__slider-tag--from"
                aria-hidden="true"
                >{{ fromLabel }}</span
              >
              <span
                class="image-comparison__slider-tag image-comparison__slider-tag--to"
                aria-hidden="true"
                >{{ toLabel }}</span
              >
              <input
                v-model.number="position"
                class="image-comparison__slider-input"
                type="range"
                min="0"
                max="100"
                :aria-label="
                  t('Divider between {from} and {to}', {
                    from: fromLabel,
                    to: toLabel,
                  })
                "
                :aria-valuetext="`${position}%`"
              />
              <!-- Only shows where the range input above is; it's never focused itself. -->
              <span
                class="image-comparison__slider-handle"
                aria-hidden="true"
                inert
              >
                <craft-button
                  data-theme="dark"
                  :aria-label="t('Divider')"
                  type="button"
                  variant="outline"
                  size="xsmall"
                  icon="arrows-left-right"
                ></craft-button>
              </span>
            </div>
          </td>
        </tr>
        <tr v-else class="image-comparison__images">
          <td
            v-for="side in [from, image]"
            :key="side === from ? 'from' : 'to'"
            :headers="headerId(side === from ? 'from' : 'to')"
          >
            <div class="image-comparison__preview" :style="{aspectRatio}">
              <img
                v-if="src(side)"
                :src="src(side)!"
                :alt="side.title"
                @load="side === from && measure($event)"
                @error="failed.add(side.key)"
              />
              <span v-else>{{ t('Preview not available.') }}</span>
            </div>
          </td>
        </tr>
      </tbody>
      <tbody
        v-for="detail in details"
        :key="detail.key"
        class="image-comparison__detail"
      >
        <tr
          class="image-comparison__label-row"
          :class="{'cp-compare-changed': differs(image, detail)}"
        >
          <th :id="headerId(`detail-${detail.key}`)" scope="rowgroup">
            {{ detail.label }}
            <span
              v-if="differs(image, detail)"
              class="image-comparison__badge"
              >{{ t('Different') }}</span
            >
          </th>
          <!-- Repeats the label above the other side's value, as the
               Compare screen does. The row header already names it. -->
          <td aria-hidden="true">
            {{ detail.label }}
            <span
              v-if="differs(image, detail)"
              class="image-comparison__badge"
              >{{ t('Different') }}</span
            >
          </td>
        </tr>
        <tr
          class="image-comparison__value-row"
          :class="{'cp-compare-changed': differs(image, detail)}"
        >
          <td
            v-for="side in ['from', 'to'] as const"
            :key="side"
            :headers="`${headerId(side)} ${headerId(`detail-${detail.key}`)}`"
          >
            {{ value(side === 'from' ? from : image, detail) }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
  .image-comparison {
    --_bleed: var(--image-comparison-bleed, 0px);
    --_gutter: var(--image-comparison-gutter, var(--c-spacing-lg));
    /* The tallest an image gets, however wide the box is. */
    --_preview-size: 24rem;
    --_slider-size: min(70vh, 40rem);

    container: image-comparison / inline-size;
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    overflow-wrap: anywhere;
  }

  /* Bled to the top and bottom as well, to a bleeding host's header and footer borders. */
  .image-comparison__table {
    inline-size: calc(100% + var(--_bleed) * 2);
    margin-block: calc(var(--_bleed) * -1);
    margin-inline: calc(var(--_bleed) * -1);
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed;
  }

  .image-comparison__table :is(th, td) {
    padding-inline: var(--_gutter);
    vertical-align: top;
    text-align: start;
  }

  .image-comparison__table tr > :first-child {
    padding-inline-start: max(var(--_bleed), var(--c-spacing-xs));
  }

  .image-comparison__table tr > :last-child {
    padding-inline-end: max(var(--_bleed), var(--c-spacing-xs));
  }

  .image-comparison__table tr > :nth-child(2) {
    border-inline-start: 1px solid var(--cp-compare-hairline);
  }

  .image-comparison__table thead th {
    padding-block: var(--c-spacing-sm);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
    background: var(--c-surface-raised);
    font-weight: 600;
  }

  /* Takes no room, but stays in the table, which its cells are named by. */
  .image-comparison__table--hidden-headings thead th {
    padding-block: 0;
    border-block-end: 0;
  }

  .image-comparison__table th craft-action-menu {
    max-width: 100%;
  }

  .image-comparison__choose {
    --c-button-width: 0;

    max-width: 100%;
  }

  .image-comparison__choose::part(content) {
    min-width: 0;
  }

  .image-comparison__choose::part(label) {
    overflow: hidden;
    text-overflow: ellipsis;
    unicode-bidi: plaintext;
  }

  .image-comparison__table .image-comparison__images > td,
  .image-comparison__table tr > .image-comparison__slider-cell {
    padding: 0;
  }

  .image-comparison__table .image-comparison__images > :first-child {
    border-inline-end: 1px solid transparent;
  }

  .image-comparison__heading {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    /* As tall as the buttons that some headings have, so all line up. */
    min-block-size: var(--c-size-control-sm);
  }

  .image-comparison__link {
    flex: none;
    margin-inline-start: auto;
  }

  .image-comparison__preview {
    display: flex;
    align-items: center;
    justify-content: center;
    max-block-size: var(--_preview-size);
    /* Narrower than its column once it's as tall as it gets. */
    margin-inline: auto;
    color: var(--c-text-quiet);
    overflow: hidden;
    text-align: center;
  }

  .image-comparison__preview img {
    inline-size: 100%;
    block-size: 100%;
    object-fit: contain;
  }

  .image-comparison__label-row > * {
    padding-block-start: var(--c-spacing-sm);
    font-size: var(--c-text-sm);
    font-weight: var(--font-weight-medium);
  }

  /* Styled like the badge on a changed field's label, which `craft-field` draws. */
  .image-comparison__badge {
    margin-inline-start: var(--c-spacing-xs);
    padding-block: 0;
    padding-inline: 0.25em;
    border: 1px solid var(--c-color-violet-border-quiet);
    border-radius: var(--c-radius-sm, 3px);
    background-color: var(--c-color-violet-fill-quiet);
    color: var(--c-color-violet-on-quiet);
    font-size: calc(11rem / 16);
    font-weight: normal;
    line-height: 1.45;
  }

  .image-comparison__value-row > td {
    padding-block-end: var(--c-spacing-sm);
  }

  .image-comparison__table tr > .image-comparison__slider-cell {
    border-inline-start: 0;
  }

  /* As wide as the image is at the tallest it gets, so the divider never
     slides over empty space beside it. */
  .image-comparison__slider {
    position: relative;
    max-inline-size: calc(var(--_slider-size) * var(--ratio));
    margin-inline: auto;
    aspect-ratio: var(--ratio);
    overflow: hidden;
  }

  .image-comparison__slider-image {
    position: absolute;
    inset: 0;
    inline-size: 100%;
    block-size: 100%;
    object-fit: contain;
  }

  .image-comparison__slider-image--from {
    clip-path: inset(0 calc(100% - var(--position)) 0 0);
  }

  :dir(rtl) .image-comparison__slider-image--from {
    clip-path: inset(0 0 0 calc(100% - var(--position)));
  }

  .image-comparison__slider-tag {
    position: absolute;
    inset-block-start: var(--c-spacing-sm);
    padding-block: var(--c-spacing-2xs, 2px);
    padding-inline: var(--c-spacing-sm);
    border-radius: var(--c-radius-sm);
    background: var(--c-color-neutral-fill-loud);
    color: var(--c-color-neutral-on-loud);
    font-size: var(--c-text-xs);
    font-weight: 600;
    pointer-events: none;
  }

  .image-comparison__slider-tag--from {
    inset-inline-start: var(--c-spacing-sm);
  }

  .image-comparison__slider-tag--to {
    inset-inline-end: var(--c-spacing-sm);
  }

  .image-comparison__slider-handle {
    position: absolute;
    inset-block: 0;
    inset-inline-start: var(--position);
    display: flex;
    align-items: center;
    justify-content: center;
    translate: -50% 0;
    pointer-events: none;
  }

  :dir(rtl) .image-comparison__slider-handle {
    translate: 50% 0;
  }

  .image-comparison__slider-handle::before {
    content: '';
    position: absolute;
    inset-block: 0;
    inset-inline-start: 50%;
    inline-size: 1px;
    background: rgb(255 255 255 / 0.55);
    box-shadow: 0 0 2px rgb(0 0 0 / 0.35);
  }

  .image-comparison__slider-handle craft-button {
    --c-form-control-border-color: var(--cp-compare-hairline);

    position: relative;
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-default);
  }

  .image-comparison__slider-input:focus-visible
    + .image-comparison__slider-handle
    craft-button {
    outline: var(--c-focus-outline-width) var(--c-focus-outline-style)
      var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
  }

  .image-comparison__slider-input {
    position: absolute;
    inset: 0;
    inline-size: 100%;
    block-size: 100%;
    margin: 0;
    opacity: 0;
    cursor: ew-resize;
  }

  @media (forced-colors: active) {
    .image-comparison__slider-handle::before {
      background: CanvasText;
    }

    .image-comparison__slider-tag {
      border: 1px solid CanvasText;
    }

    .image-comparison__slider-input:focus-visible
      + .image-comparison__slider-handle
      craft-button {
      outline-color: Highlight;
    }
  }

  @container image-comparison (width < 30rem) {
    .image-comparison > * {
      --_gutter: var(--c-spacing-sm);
      --_preview-size: 14rem;
      --_slider-size: min(60vh, 40rem);
    }
  }
</style>
