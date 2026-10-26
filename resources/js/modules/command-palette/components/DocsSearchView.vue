<script setup lang="ts">
  /**
   * Searches the Craft CMS documentation, Knowledge Base, and glossary on
   * craftcms.com, for the palette's “Search Craft CMS documentation” item.
   *
   * Like the palette's own search, the input is a plain search field with the
   * results in a `craft-grid` after it. Each result is a link that opens on
   * craftcms.com in a new tab, leaving the palette open.
   */
  import {actionClient, t} from '@craftcms/ui';
  import {docs as docsUrl} from '@actions/CommandPaletteController';
  import {
    computed,
    nextTick,
    onBeforeUnmount,
    shallowRef,
    useId,
    useTemplateRef,
    watch,
  } from 'vue';
  import CraftInputSearch from '@craftcms/ui/vue/CraftInputSearch.vue';
  import {fromServerItem, type CommandPaletteItem} from '../types';
  import {resetResultGrids} from '../grids';
  import ResultSection from './ResultSection.vue';

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
  let timer: ReturnType<typeof setTimeout> | undefined;
  let controller: AbortController | null = null;

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

  const resultsRegion = useTemplateRef<HTMLElement>('resultsRegion');

  // New results start from the top, unless focus is among them already.
  watch(results, async () => {
    await nextTick();

    if (!resultsRegion.value?.contains(document.activeElement)) {
      resetResultGrids(resultsRegion.value);
    }
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

    searching.value = false;
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
        :aria-label="t('Search Craft CMS documentation')"
        autocapitalize="off"
        spellcheck="false"
        data-autofocus
      />
    </CraftInputSearch>

    <div
      ref="resultsRegion"
      class="command-palette-docs__results"
      :aria-busy="searching"
    >
      <div
        v-if="searching && !results.length"
        class="command-palette-docs__note"
      >
        <craft-spinner :label="t('Searching…')"></craft-spinner>
      </div>
      <ResultSection
        v-if="results.length"
        :grid-id="`${id}-results`"
        :heading="t('Results')"
        :items="results"
        new-tab
      />
      <craft-empty
        v-else-if="statusMessage && !searching"
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
