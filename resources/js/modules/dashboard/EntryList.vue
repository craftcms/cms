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

    const opener = (event.currentTarget as HTMLElement)
      .closest('li')
      ?.querySelector<HTMLElement>('.entry-list__title');

    void openSlideout(item.url, {opener});
  }

  defineSlots<{
    details?: (props: {item: T}) => unknown;
    aside?: (props: {item: T}) => unknown;
  }>();
</script>

<template>
  <ul class="entry-list" role="list">
    <li v-for="item in items" :key="item.id" class="entry-list__item">
      <div
        v-if="item.thumbHtml"
        class="entry-list__thumb"
        aria-hidden="true"
        @dblclick="openInSlideout(item, $event)"
        v-html="item.thumbHtml"
      ></div>
      <!-- Drawn as an entry type's icon thumbnail is, for entries without one. -->
      <div
        v-else
        class="entry-list__thumb"
        aria-hidden="true"
        @dblclick="openInSlideout(item, $event)"
      >
        <div class="thumb icon-thumb entry-list__fallback-thumb">
          <craft-icon :name="fallbackIcon"></craft-icon>
        </div>
      </div>
      <div class="entry-list__text">
        <CpLink
          v-if="item.url"
          :href="item.url"
          bare
          class="entry-list__title"
          >{{ item.title }}</CpLink
        >
        <span v-else class="entry-list__title">{{ item.title }}</span>
        <p v-if="$slots.details" class="entry-list__details">
          <slot name="details" :item="item" />
        </p>
      </div>
      <div v-if="$slots.aside" class="entry-list__aside">
        <slot name="aside" :item="item" />
      </div>
    </li>
  </ul>
</template>

<style scoped>
  /* Spaced like the Feed widget's items, which share these measurements. */
  .entry-list {
    display: grid;
    gap: calc(var(--c-spacing) * 3);
  }

  .entry-list__item {
    display: grid;
    grid-template-columns: calc(40rem / 16) minmax(0, 1fr) auto;
    align-items: center;
    column-gap: var(--c-spacing-md);
  }

  .entry-list__thumb {
    --c-thumbnail-size: calc(40rem / 16);
    --c-thumb-icon-size: calc(40rem / 16);
    display: flex;
    align-items: center;
    justify-content: center;
    inline-size: calc(40rem / 16);
    block-size: calc(40rem / 16);
    overflow: hidden;
    border-radius: var(--c-radius-sm);
    /* A double-click opens the entry rather than selecting text by it. */
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

  /* The tone entry types' icons take in a colored widget, so a fallback is no
     darker than they are. */
  .entry-list__fallback-thumb {
    color: var(--c-color-neutral-fill-loud);
    container-type: size;
    inline-size: 100%;
    block-size: 100%;
  }

  .entry-list__fallback-thumb > craft-icon {
    --c-icon-size: 55cqmin;
  }

  .entry-list__text {
    min-width: 0;
  }

  .entry-list__title {
    display: block;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
  }

  .entry-list__details {
    margin: 0;
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .entry-list__aside {
    display: flex;
    align-items: center;
  }
</style>
