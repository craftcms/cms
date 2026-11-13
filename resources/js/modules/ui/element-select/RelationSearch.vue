<script setup lang="ts">
  /**
   * A relation field's search input, as Craft 5's `elementselect` had: typing
   * finds elements to relate, and an Entries field limited to one section the
   * user can publish in also offers to create the entry it didn't find.
   *
   * The input is a combobox over a listbox of results, after the APG's list
   * autocomplete combobox, as the command palette's search is: focus stays in
   * the input, the arrow keys move the active option, and Return or a click
   * chooses it.
   */
  import '@craftcms/ui/components/option/option';
  import {actionClient, t} from '@craftcms/ui';
  import {Dropdown} from '@craftcms/ui/utilities/overlay-dropdown';
  import CraftInputSearch from '@craftcms/ui/vue/CraftInputSearch.vue';
  import {
    computed,
    nextTick,
    onBeforeUnmount,
    ref,
    shallowRef,
    useId,
    watch,
  } from 'vue';
  import SearchController from '@actions/Elements/SearchController';
  import CreateEntryController from '@actions/Entries/CreateEntryController';
  import EditElementController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/EditElementController';
  import {openSlideout} from '@/common/slideouts';
  import {useMessages} from '@/modules/messages/useMessages';
  import {useResultsListbox} from '@/modules/command-palette/useResultsListbox';
  import type {ElementPresentation, ElementSelectProps} from './types';

  const props = defineProps<{
    field: ElementSelectProps & {
      search: NonNullable<ElementSelectProps['search']>;
    };
    /**
     * Already related or unrelatable, so they're listed as unavailable, as Craft 5's menu
     * listed them: dimmed, skipped by the arrow keys, and not chosen.
     */
    excludeIds: number[];
  }>();

  const emit = defineEmits<{
    (event: 'select', element: ElementPresentation): void;
  }>();

  /** How long typing has to pause before the server is asked. */
  const SEARCH_DEBOUNCE = 300;

  type SearchResult = {
    id: number;
    title: string | null;
    html: string;
    exclude: boolean;
  };

  type Row = {
    optionId: string;
    /** `null` for the option that creates an entry. */
    result: SearchResult | null;
  };

  const id = useId();
  const messages = useMessages();
  const query = shallowRef('');
  const term = computed(() => query.value.trim());
  const results = shallowRef<SearchResult[]>([]);
  const exactMatch = shallowRef(false);
  const searching = shallowRef(false);
  const failed = shallowRef(false);
  const focused = shallowRef(false);
  const selectedIndex = shallowRef(0);

  const input = ref<HTMLInputElement | null>(null);
  const layer = ref<HTMLElement | null>(null);
  const pane = ref<HTMLElement | null>(null);

  let timer: ReturnType<typeof setTimeout> | undefined;
  let controller: AbortController | null = null;

  const typeName = computed(() => props.field.elementDisplayName);
  const canCreate = computed(
    () =>
      !!props.field.createSection &&
      !!term.value &&
      !searching.value &&
      !exactMatch.value
  );

  const rows = computed((): Row[] => [
    ...results.value.map((result) => ({
      optionId: `${id}-option-${result.id}`,
      result,
    })),
    ...(canCreate.value
      ? [{optionId: `${id}-option-create`, result: null}]
      : []),
  ]);

  const {listboxId, selectedRow, comboboxAttributes} = useResultsListbox(
    id,
    rows,
    selectedIndex
  );

  function available(row: Row | undefined): boolean {
    return !!row && !row.result?.exclude;
  }

  /** The available option `step` along from the active one, wrapping. */
  function availableAfter(step: 1 | -1): number | null {
    const count = rows.value.length;

    for (let offset = 1; offset <= count; offset++) {
      const index =
        (selectedIndex.value + step * offset + count * count) % count;

      if (available(rows.value[index])) {
        return index;
      }
    }

    return null;
  }

  const open = computed(
    () => focused.value && !!term.value && rows.value.length > 0
  );

  const statusMessage = computed(() => {
    if (!term.value || searching.value) {
      return '';
    }

    if (failed.value) {
      return t('A server error occurred.');
    }

    return results.value.length
      ? t('{count, plural, =1{# result} other{# results}}', {
          count: results.value.length,
        })
      : t('No results');
  });

  const dropdown = new Dropdown(() =>
    layer.value && pane.value && input.value
      ? {layer: layer.value, pane: pane.value, reference: input.value}
      : undefined
  );

  watch(open, async (isOpen) => {
    await nextTick();

    if (isOpen) {
      dropdown.show();
    } else {
      dropdown.hide();
    }
  });

  watch(rows, () => nextTick(() => dropdown.update()));

  watch(term, (value) => {
    clearTimeout(timer);
    controller?.abort();

    if (!value) {
      results.value = [];
      exactMatch.value = false;
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
    dropdown.hide();
  });

  async function search(value: string): Promise<void> {
    const current = new AbortController();
    controller = current;

    try {
      const {data} = await actionClient.post<{
        elements: SearchResult[];
        exactMatch: boolean;
      }>(
        SearchController.url(),
        {
          elementType: props.field.elementType,
          siteId: props.field.search.siteId,
          search: value,
          criteria: props.field.search.criteria,
          condition: props.field.selectionCondition,
          ...props.field.referenceElement,
          excludeIds: props.excludeIds,
        },
        {signal: current.signal}
      );

      // The search has moved on; a newer request has the results.
      if (value !== term.value) {
        return;
      }

      results.value = data.elements;
      exactMatch.value = data.exactMatch;
      failed.value = false;
    } catch {
      if (current.signal.aborted || value !== term.value) {
        return;
      }

      results.value = [];
      exactMatch.value = false;
      failed.value = true;
    }

    searching.value = false;
    // The first available one is active, or none is.
    selectedIndex.value = rows.value.findIndex(available);
  }

  function onKeydown(event: KeyboardEvent): void {
    switch (event.key) {
      case 'ArrowDown':
      case 'ArrowUp': {
        const index = availableAfter(event.key === 'ArrowDown' ? 1 : -1);

        if (index === null || !open.value) {
          return;
        }

        event.preventDefault();
        selectedIndex.value = index;
        void nextTick(() =>
          document
            .getElementById(rows.value[index]!.optionId)
            ?.scrollIntoView({block: 'nearest'})
        );
        break;
      }
      case 'Enter':
        if (event.isComposing || !open.value || !selectedRow.value) {
          return;
        }

        event.preventDefault();

        // The results are for an earlier query until the search answers.
        if (!searching.value) {
          void choose(selectedRow.value);
        }
        break;
    }
  }

  async function choose(row: Row): Promise<void> {
    if (searching.value || !available(row)) {
      return;
    }

    const title = term.value;

    if (row.result) {
      clear();
      emit('select', {
        id: row.result.id,
        label: row.result.title ?? String(row.result.id),
        siteId: props.field.search.siteId,
      });

      return;
    }

    clear();
    await createEntry(title);
  }

  function clear(): void {
    query.value = '';
  }

  /**
   * Creates an entry titled after the query and opens it, then relates it once
   * it's saved, as Craft 5's `EntrySelectInput` did. Closing it unsaved leaves
   * the field as it was. If it can't be created, the query comes back.
   */
  async function createEntry(title: string): Promise<void> {
    const siteId = props.field.search.siteId;
    let entry: {id: number; draftId: number; title: string | null};

    try {
      const {data} = await actionClient.post<{entry: typeof entry}>(
        CreateEntryController[
          '/{cpTrigger?}/{actionTrigger?}/entries/create'
        ].url(),
        {siteId, section: props.field.createSection, title}
      );
      entry = data.entry;
    } catch {
      query.value = title;
      messages.error(t('Couldn’t create {type}.', {type: typeName.value}));

      return;
    }

    void openSlideout(
      EditElementController['/{cpTrigger?}/{actionTrigger?}/elements/edit'].url(
        undefined,
        {
          query: {
            elementType: props.field.elementType,
            elementId: entry.id,
            draftId: entry.draftId,
            siteId,
            fresh: 1,
          },
        }
      ),
      {
        opener: input.value,
        onSaved: ({draft, data: saved}) => {
          if (draft) {
            return;
          }

          const element = (saved?.element ?? {}) as {title?: string | null};

          emit('select', {
            id: entry.id,
            label: element.title || entry.title || title,
            siteId,
          });
        },
      }
    );
  }

  defineExpose({
    focus: () => input.value?.focus(),
    isConnected: () => !!input.value?.isConnected,
  });
</script>

<template>
  <div class="relation-search">
    <CraftInputSearch
      v-model="query"
      :placeholder="t('Search')"
      @focusin="focused = true"
      @focusout="focused = false"
    >
      <input
        ref="input"
        slot="input"
        type="text"
        v-bind="comboboxAttributes"
        :aria-expanded="open ? 'true' : 'false'"
        :aria-activedescendant="open ? selectedRow?.optionId : undefined"
        :aria-busy="searching ? 'true' : undefined"
        autocapitalize="off"
        spellcheck="false"
        @keydown="onKeydown"
      />
      <craft-spinner
        slot="after"
        style="--size: 1em"
        .visible="searching"
        :label="t('Searching…')"
      ></craft-spinner>
    </CraftInputSearch>

    <div ref="layer" class="relation-search__layer" popover="manual">
      <!-- A press keeps focus in the search input, which owns the active option. -->
      <div
        :id="listboxId"
        ref="pane"
        role="listbox"
        class="relation-search__listbox"
        :aria-label="t('Results')"
        @mousedown.prevent
      >
        <craft-option
          v-for="(row, index) in rows"
          :id="row.optionId"
          :key="row.optionId"
          .active="index === selectedIndex"
          @click="choose(row)"
          .disabled="!available(row)"
          @pointermove="available(row) && (selectedIndex = index)"
        >
          <span v-if="row.result" v-html="row.result.html"></span>
          <span v-else class="relation-search__create">
            <craft-icon name="plus" aria-hidden="true"></craft-icon>
            {{ t('Create {type}', {type: typeName}) }}: {{ term }}
          </span>
        </craft-option>
      </div>
    </div>

    <div class="sr-only" role="status">{{ statusMessage }}</div>
  </div>
</template>

<style scoped>
  .relation-search__layer {
    position: fixed;
    inset: auto;
    z-index: var(--c-layer-popover, 9999);
    margin: 0;
    padding: 0;
    border: 0;
    background: none;
    color: inherit;
    overflow: visible;
  }

  .relation-search__layer:not([data-open]) {
    display: none;
  }

  .relation-search__listbox {
    display: grid;
    gap: var(--c-spacing-xs);
    max-inline-size: min(calc(360rem / 16), calc(100vw - var(--c-spacing-lg)));
    max-block-size: calc(240rem / 16);
    overflow: auto;
    padding: var(--c-spacing-sm);
    border: 1px solid var(--c-form-control-border-color);
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-overlay);
    box-shadow: var(--c-shadow-sm);
  }

  .relation-search__listbox craft-option[disabled] {
    opacity: 0.5;
    cursor: not-allowed;
  }

  .relation-search__create {
    display: inline-flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }
</style>
