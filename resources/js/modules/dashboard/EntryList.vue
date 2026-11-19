<script setup lang="ts" generic="T extends EntryListItem">
  import CpLink from '@/common/components/CpLink.vue';
  import {openSlideout} from '@/common/slideouts';
  import type {EntryListItem} from './types';

  withDefaults(
    defineProps<{
      items: T[];
      /** The icon an item without a thumbnail shows in its place. */
      fallbackIcon?: string;
    }>(),
    {fallbackIcon: 'newspaper'}
  );

  /**
   * Opens an entry in a slideout when its thumbnail is double-clicked, as an
   * element index's row does. Its title link is the keyboard's way in, and
   * where focus returns once the slideout closes. A saved entry reloads the
   * dashboard's widgets on its own.
   */
  function openInSlideout(item: T, event: MouseEvent): void {
    if (!item.url) {
      return;
    }

    const onThumbnail = event
      .composedPath()
      .some(
        (target) =>
          target instanceof Element &&
          (target.slot === 'thumbnail' || target.part?.contains('thumbnail'))
      );

    if (!onThumbnail) {
      return;
    }

    const opener = (
      event.currentTarget as HTMLElement
    ).querySelector<HTMLElement>('.entry-list__title');

    void openSlideout(item.url, {opener});
  }

  defineSlots<{
    details?: (props: {item: T}) => unknown;
    aside?: (props: {item: T}) => unknown;
  }>();
</script>

<template>
  <ul class="entry-list" role="list">
    <li v-for="item in items" :key="item.id">
      <craft-media-item
        class="entry-list__item"
        :icon="item.thumbHtml ? undefined : fallbackIcon"
        truncate
        @dblclick="openInSlideout(item, $event)"
      >
        <div
          v-if="item.thumbHtml"
          slot="thumbnail"
          class="entry-list__thumb"
          aria-hidden="true"
          v-html="item.thumbHtml"
        ></div>
        <CpLink
          v-if="item.url"
          slot="heading"
          :href="item.url"
          bare
          class="entry-list__title"
          >{{ item.title }}</CpLink
        >
        <span v-else slot="heading" class="entry-list__title">{{
          item.title
        }}</span>
        <span v-if="$slots.details">
          <slot name="details" :item="item" />
        </span>
        <div v-if="$slots.aside" slot="aside" class="entry-list__aside">
          <slot name="aside" :item="item" />
        </div>
      </craft-media-item>
    </li>
  </ul>
</template>

<style scoped>
  /* Spaced like the Feed widget's items, which share these measurements. */
  .entry-list {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: calc(var(--c-spacing) * 3);
  }

  /* A double-click opens the entry rather than selecting text by it. */
  .entry-list__item::part(thumbnail) {
    user-select: none;
  }

  /* In a colored widget, an entry type's icon takes the widget's color rather
     than its own, as the rest of the widget does. Neutral widgets, black
     included, keep the entry type's color. */
  craft-card[data-color]:not(
      [data-color='white'],
      [data-color='black'],
      [data-color='gray'],
      [data-color='slate']
    )
    .entry-list__thumb
    :deep(.icon-thumb[data-color]) {
    color: var(--c-color-neutral-fill-loud);
  }

  .entry-list__aside {
    display: flex;
    align-items: center;
  }
</style>
