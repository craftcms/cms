<script setup lang="ts">
  /**
   * Searches the Craft CMS documentation, Knowledge Base, and glossary on
   * craftcms.com, for the palette's “Search Craft CMS documentation” item.
   *
   * Like the palette's own search, the input is a combobox over a listbox of
   * results: focus stays in the input, the arrow keys move the active option,
   * and Return or a click opens it on craftcms.com in a new tab, leaving the
   * palette open.
   */
  import {actionClient, t} from '@craftcms/ui';
  import {docs as docsUrl} from '@actions/CommandPaletteController';
  import {computed, onBeforeUnmount, shallowRef, useId, watch} from 'vue';
  import CraftInputSearch from '@craftcms/ui/vue/CraftInputSearch.vue';
  import {fromServerItem, type CommandPaletteItem} from '../types';
  import ResultSection, {type ResultRow} from './ResultSection.vue';

  defineEmits<{
    (event: 'back'): void;
  }>();

  /** How long typing has to pause before craftcms.com is asked. */
  const SEARCH_DEBOUNCE = 300;

  const id = useId();
  const query = shallowRef('');
  const results = shallowRef<CommandPaletteItem[]>([]);
  const searching = shallowRef(false);
  const failed = shallowRef(false);
  const selectedIndex = shallowRef(0);
  /** The row under the pointer, only highlighted: the keys and clicks choose. */
  const hoveredIndex = shallowRef(-1);
  let timer: ReturnType<typeof setTimeout> | undefined;
  let controller: AbortController | null = null;

  const rows = computed((): ResultRow[] =>
    results.value.map((item, index) => ({
      item,
      index,
      optionId: `${id}-option-${index}`,
    }))
  );
  const selectedRow = computed(() => rows.value[selectedIndex.value] ?? null);
  const term = computed(() => query.value.trim());

  const statusMessage = computed(() => {
    if (!term.value || searching.value) {
      return '';
    }

    if (failed.value) {
      return t('Couldn’t search the Craft CMS documentation.');
    }

    return results.value.length
      ? t('{count, plural, =1{# result} other{# results}}', {
          count: results.value.length,
        })
      : t('No results');
  });

  watch(term, (value) => {
    clearTimeout(timer);
    controller?.abort();

    if (!value) {
      results.value = [];
      searching.value = false;
      failed.value = false;

      return;
    }

    searching.value = true;
    timer = setTimeout(() => void search(value), SEARCH_DEBOUNCE);
  });

  onBeforeUnmount(() => {
    clearTimeout(timer);
    controller?.abort();
  });

  async function search(value: string): Promise<void> {
    const current = new AbortController();
    controller = current;

    try {
      const {data} = await actionClient.get<{
        results: CraftCms.Cms.Cp.CommandPalette.CommandPaletteItem[];
      }>(docsUrl.url(), {params: {query: value}, signal: current.signal});

      // The search has moved on; a newer request has the results.
      if (value !== term.value) {
        return;
      }

      results.value = data.results.map(fromServerItem);
      failed.value = false;
    } catch {
      if (current.signal.aborted || value !== term.value) {
        return;
      }

      results.value = [];
      failed.value = true;
    }

    selectedIndex.value = 0;
    hoveredIndex.value = -1;
    searching.value = false;
  }

  function open(item: CommandPaletteItem): void {
    if (item.href) {
      window.open(item.href, '_blank', 'noopener');
    }
  }

  function onInputKeydown(event: KeyboardEvent): void {
    const count = rows.value.length;

    switch (event.key) {
      case 'ArrowDown':
      case 'ArrowUp':
        event.preventDefault();

        if (count) {
          hoveredIndex.value = -1;
          selectedIndex.value =
            (selectedIndex.value +
              (event.key === 'ArrowDown' ? 1 : -1) +
              count) %
            count;
          document
            .getElementById(selectedRow.value!.optionId)
            ?.scrollIntoView({block: 'nearest'});
        }
        break;
      case 'Enter':
        if (event.isComposing || !selectedRow.value) {
          return;
        }

        event.preventDefault();
        open(selectedRow.value.item);
        break;
    }
  }

  // The palette's keyboard instructions say what Escape does, which depends on it.
  defineExpose({query});

  /** A click opens the result in a new tab, modified or not, as Return does. */
  function onActivate(item: CommandPaletteItem): void {
    open(item);
  }
</script>

<template>
  <div class="command-palette-docs">
    <CraftInputSearch
      v-model="query"
      :placeholder="t('Search the docs, Knowledge Base, and glossary')"
    >
      <input
        slot="input"
        type="text"
        role="combobox"
        :aria-expanded="rows.length ? 'true' : 'false'"
        aria-autocomplete="list"
        :aria-controls="`${id}-listbox`"
        :aria-activedescendant="selectedRow?.optionId"
        autocapitalize="off"
        spellcheck="false"
        data-autofocus
        @keydown="onInputKeydown"
      />
    </CraftInputSearch>

    <div class="command-palette-docs__results" :aria-busy="searching">
      <div v-if="searching && !rows.length" class="command-palette-docs__note">
        <craft-spinner :label="t('Searching…')"></craft-spinner>
      </div>
      <div
        :id="`${id}-listbox`"
        role="listbox"
        :aria-label="t('Results')"
        :hidden="!rows.length"
        @pointerleave="hoveredIndex = -1"
      >
        <ResultSection
          v-if="rows.length"
          :group-id="`${id}-results`"
          :heading="t('Results')"
          :rows="rows"
          :selected-index="selectedIndex"
          :hovered-index="hoveredIndex"
          @activate="onActivate"
          @hover="(index) => (hoveredIndex = index)"
        />
      </div>
      <craft-empty
        v-if="!rows.length && statusMessage && !searching"
        class="command-palette-docs__empty"
        :label="statusMessage"
      ></craft-empty>
    </div>
    <div class="sr-only" role="status">{{ statusMessage }}</div>
  </div>
</template>

<style scoped>
  .command-palette-docs {
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: var(--c-spacing-md);
  }

  .command-palette-docs :deep(.command-palette-row__icon) {
    align-self: start;
    justify-content: start;
    inline-size: auto;
    margin-inline-end: var(--c-spacing-sm);
    block-size: 1lh;
  }

  /* The book glyph is narrower than its square box; this puts its edge flush with the heading. */
  .command-palette-docs :deep(.command-palette-row__icon craft-icon) {
    margin-inline-start: -0.15em;
  }

  /* Fills the space below the search box, like an empty table, so the message sits in its middle. */
  .command-palette-docs__results {
    display: flex;
    flex: 1;
    flex-direction: column;
  }

  .command-palette-docs__empty {
    display: grid;
    flex: 1;
  }

  .command-palette-docs__note {
    display: flex;
    justify-content: center;
    margin: 0;
    padding: var(--c-spacing-lg);
    color: var(--c-text-quiet);
  }
</style>
