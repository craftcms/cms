import {computed, type Ref} from 'vue';
import type {SourceRow} from '@/modules/elements/index/components/customize-sources/types';

/**
 * Orders the "Customize sources" modal's sources: the page being viewed, the
 * blocks a heading carries with it, reordering within a page, and moving
 * sources to another page.
 */
export function useSourceOrdering(options: {
  sources: Ref<SourceRow[]>;
  multiPage: Readonly<Ref<boolean>>;
  selectedPage: Readonly<Ref<string | null>>;
}) {
  const {sources, multiPage, selectedPage} = options;

  function onViewedPage(source: SourceRow): boolean {
    return !multiPage.value || source.page === selectedPage.value;
  }

  /** The sources on the page being viewed — all of them, on a single page. */
  const visibleSources = computed(() => sources.value.filter(onViewedPage));

  /**
   * The sources that move with each source: a heading brings every source
   * beneath it on its page, up to the next heading; anything else moves alone.
   */
  const sourceBlocks = computed(() => {
    const blocks = new Map<SourceRow, SourceRow[]>();
    const headings = new Map<string, SourceRow[]>();

    for (const row of sources.value) {
      const page = multiPage.value ? row.page : '';

      if (row.type === 'heading') {
        const block = [row];
        blocks.set(row, block);
        headings.set(page, block);
      } else {
        blocks.set(row, [row]);
        headings.get(page)?.push(row);
      }
    }

    return blocks;
  });

  /** How many visible rows move with the one at `index`. */
  function sourceSpan(index: number): number {
    const source = visibleSources.value[index];

    return source ? (sourceBlocks.value.get(source)?.length ?? 0) : 1;
  }

  /**
   * Moves the visible rows starting at `from` (a heading carries its sources) so
   * the first of them lands at `to`. Only the visible sources are listed, but
   * order is kept in the full list, so the reordered page is written back into
   * the slots its sources already occupy there.
   */
  function reorderSources(from: number, to: number): void {
    const visible = [...visibleSources.value];
    const block = visible.splice(from, sourceSpan(from));
    visible.splice(to, 0, ...block);

    let next = 0;
    sources.value = sources.value.map((source) =>
      onViewedPage(source) ? visible[next++]! : source
    );
  }

  /** Moves `rows` to the end of `page`, after the sources already on it. */
  function moveRows(rows: SourceRow[], page: string): void {
    const rest = sources.value.filter((row) => !rows.includes(row));
    let last = -1;
    rest.forEach((row, index) => {
      if (row.page === page) last = index;
    });

    for (const row of rows) row.page = page;
    rest.splice(last === -1 ? rest.length : last + 1, 0, ...rows);
    sources.value = rest;
  }

  /**
   * Moves a source — and, for a heading, the sources beneath it — to the end of
   * another page. Returns the sources that moved.
   */
  function moveToPage(key: string, page: string): SourceRow[] {
    const source = sources.value.find((row) => row.key === key);
    if (!source || source.page === page) return [];

    const block = sourceBlocks.value.get(source) ?? [];
    moveRows(block, page);

    return block;
  }

  return {visibleSources, sourceSpan, reorderSources, moveRows, moveToPage};
}
