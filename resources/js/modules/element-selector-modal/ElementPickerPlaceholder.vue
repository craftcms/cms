<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import PlaceholderShape from '@/common/components/PlaceholderShape.vue';
  import {prefixedKey} from '@/common/composables/useStorage';
  import type {ViewState} from '@/modules/elements/types/view-state';

  /**
   * Stands in for the picker until the modal's first payload arrives. Nothing
   * about the index is known yet besides the view mode it was last left in,
   * which comes from the same storage the index restores it from. Modal
   * payloads always render in the `modal` context, whatever the opener's.
   */
  const props = defineProps<{elementType: string}>();

  function storedMode(): ViewState['mode'] {
    try {
      const stored = localStorage.getItem(
        prefixedKey(`elementindex.${props.elementType}.modal`)
      );

      return (stored && JSON.parse(stored)?.mode) || 'table';
    } catch {
      return 'table';
    }
  }

  const grid = ['thumbs', 'cards'].includes(storedMode());
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
    <ul v-if="grid" class="picker-placeholder__grid" aria-hidden="true">
      <li v-for="index in 8" :key="index">
        <PlaceholderShape class="picker-placeholder__tile" />
        <PlaceholderShape class="picker-placeholder__label" />
      </li>
    </ul>
    <ul v-else class="picker-placeholder__rows" aria-hidden="true">
      <li v-for="index in 5" :key="index">
        <PlaceholderShape class="picker-placeholder__checkbox" />
        <PlaceholderShape :style="{maxInlineSize: rowWidths[index - 1]}" />
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

  .picker-placeholder__rows,
  .picker-placeholder__grid {
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

  .picker-placeholder__checkbox {
    flex: none;
    inline-size: 1rem;
    block-size: 1rem;
  }

  .picker-placeholder__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
    gap: var(--c-spacing-lg);
  }

  .picker-placeholder__grid li {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-sm);
  }

  .picker-placeholder__label {
    inline-size: 66%;
  }

  .picker-placeholder__tile {
    aspect-ratio: 4 / 3;
    block-size: auto;
    border-radius: var(--c-radius-md);
  }
</style>
