import {computed, nextTick, type InputHTMLAttributes, type Ref} from 'vue';
import type {ResultRow} from './types';

/**
 * What a palette search's input shares with its results, after the APG's
 * list autocomplete combobox: the input points to the listbox, says whether
 * it has options, and which one is active. Focus stays in the input, and the
 * options are only ever active, never focused.
 */
export function useResultsListbox(
  id: string,
  rows: Ref<ResultRow[]>,
  selectedIndex: Ref<number>
) {
  const listboxId = `${id}-listbox`;
  const selectedRow = computed(() => rows.value[selectedIndex.value] ?? null);

  /** The search input's attributes as the listbox's combobox. */
  const comboboxAttributes = computed(
    (): InputHTMLAttributes => ({
      role: 'combobox',
      'aria-expanded': rows.value.length ? 'true' : 'false',
      'aria-autocomplete': 'list',
      'aria-controls': listboxId,
      'aria-activedescendant': selectedRow.value?.optionId,
    })
  );

  /** The option `step` along from the active one, wrapping, if there are any. */
  function indexAfter(step: number): number | null {
    const count = rows.value.length;

    return count ? (selectedIndex.value + step + count) % count : null;
  }

  async function scrollSelectedIntoView(): Promise<void> {
    await nextTick();
    const row = selectedRow.value;

    if (row) {
      document.getElementById(row.optionId)?.scrollIntoView({block: 'nearest'});
    }
  }

  return {
    listboxId,
    selectedRow,
    comboboxAttributes,
    indexAfter,
    scrollSelectedIntoView,
  };
}
