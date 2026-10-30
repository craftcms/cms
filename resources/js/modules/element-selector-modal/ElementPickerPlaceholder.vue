<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import PlaceholderShape from '@/common/components/PlaceholderShape.vue';
  import {useSelectable} from '@/common/composables/useSelectable';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import ElementThumbs from '@/modules/elements/components/ElementThumbs.vue';
  import {readModalIndexLook} from './modalIndexLook';

  /**
   * Stands in for the picker until the modal's first payload arrives, drawn
   * the way this element type's modal last drew its index. With nothing to go
   * on, it draws table rows, which every index offers.
   */
  const props = defineProps<{elementType: string}>();

  const look = readModalIndexLook(props.elementType);
  const mode = look?.mode ?? 'table';
  const hasThumbs = Boolean(look?.cardThumbs);
  // The card and thumb bodies need one, though placeholders never select.
  const selection = useSelectable<string | number>({ids: []});
  const rowWidths = ['60%', '80%', '45%', '70%', '50%'];
</script>

<template>
  <div class="picker-placeholder" role="status">
    <span class="sr-only">{{ t('Loading…') }}</span>
    <div class="picker-placeholder__toolbar" aria-hidden="true">
      <PlaceholderShape class="picker-placeholder__control" />
      <PlaceholderShape
        class="picker-placeholder__control picker-placeholder__search"
      />
      <PlaceholderShape class="picker-placeholder__control" />
    </div>
    <div v-if="mode === 'cards'" class="picker-placeholder__grid">
      <ElementCards
        :selection="selection"
        :placeholders="8"
        :placeholder-thumb="look?.cardThumbs ?? null"
      />
    </div>
    <div v-else-if="mode === 'thumbs'" class="picker-placeholder__grid">
      <ElementThumbs
        :selection="selection"
        :placeholders="8"
        :thumb-size="look?.thumbSize"
      />
    </div>
    <ul v-else class="picker-placeholder__rows" aria-hidden="true">
      <li v-for="index in 5" :key="index">
        <PlaceholderShape class="picker-placeholder__checkbox" />
        <craft-chip
          v-if="hasThumbs"
          appearance="plain"
          show-thumb
          :thumb-size="look?.thumbSize"
        >
          <PlaceholderShape
            slot="thumbnail"
            class="picker-placeholder__thumb"
          />
          <PlaceholderShape
            class="picker-placeholder__label"
            :style="{inlineSize: rowWidths[index - 1]}"
          />
        </craft-chip>
        <PlaceholderShape
          v-else
          :style="{maxInlineSize: rowWidths[index - 1]}"
        />
      </li>
    </ul>
  </div>
</template>

<style scoped>
  .picker-placeholder {
    display: flex;
    flex-direction: column;
    background: var(--c-surface-overlay);
  }

  .picker-placeholder__toolbar {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    box-sizing: border-box;
    min-block-size: var(--c-modal-header-height);
    padding-block: var(--c-spacing-sm);
    padding-inline: var(--cp-container-padding);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .picker-placeholder__control {
    flex: none;
    inline-size: 5rem;
    block-size: 2rem;
  }

  .picker-placeholder__search {
    flex: 1;
  }

  .picker-placeholder__rows {
    margin: 0;
    padding-block: var(--c-spacing-md);
    padding-inline: var(--cp-container-padding);
    list-style: none;
  }

  .picker-placeholder__rows li {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
    block-size: 3.5rem;
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .picker-placeholder__rows craft-chip {
    flex: 1;
  }

  .picker-placeholder__checkbox {
    flex: none;
    inline-size: 1rem;
    block-size: 1rem;
  }

  .picker-placeholder__thumb {
    inline-size: var(--c-thumbnail-size);
    block-size: var(--c-thumbnail-size);
  }

  .picker-placeholder__label {
    min-inline-size: 8rem;
  }

  .picker-placeholder__grid {
    padding-block: var(--c-spacing-lg);
    padding-inline: var(--cp-container-padding);
  }
</style>
