import {ref, type Ref} from 'vue';
import {t, type ReorderMove} from '@craftcms/ui';
import type {ActionItem} from '@/common/types';
import {useAnnouncer} from '@/common/composables/useAnnouncer';
import {
  pageNameId,
  type PageRow,
  type SourceRow,
  type SourcesResponse,
} from '@/modules/elements/index/components/customize-sources/types';

/**
 * The "Customize sources" modal's pages: the list, its settings modal, and
 * adding, renaming, reordering and removing pages. Sources follow their page
 * when it's renamed or removed.
 */
export function useSourcePages(options: {
  sources: Ref<SourceRow[]>;
  selectedPage: Ref<string | null>;
  /** Moves sources to the end of a page. */
  moveRows: (rows: SourceRow[], page: string) => void;
  /**
   * Called once a page is removed, with the page that took its sources, so the
   * caller can move selection and focus there.
   */
  onRemoved: (page: PageRow, neighbor: PageRow | undefined) => void;
}) {
  const {sources, selectedPage, moveRows, onRemoved} = options;
  const {announce} = useAnnouncer();

  const pages = ref<PageRow[]>([]);

  /** The page being edited in the settings modal, or null when adding one. */
  const editingPage = ref<PageRow | null>(null);
  const pageModalActive = ref(false);

  /** Lists the pages the sources are on, in order, with their settings. */
  function resetPages(data: SourcesResponse): void {
    if (!data.multiPage) {
      pages.value = [];
      return;
    }

    const names: string[] = [];
    for (const source of data.sources) {
      if (source.page && !names.includes(source.page)) names.push(source.page);
    }

    pages.value = names.map((name) => ({
      name,
      icon: data.pageSettings?.[name]?.icon ?? null,
    }));
  }

  function openPageSettings(page: PageRow | null): void {
    editingPage.value = page;
    pageModalActive.value = true;
  }

  function savePage(name: string, icon: string | null): void {
    if (editingPage.value) {
      updatePage(editingPage.value, name, icon);
    } else {
      addPage(name, icon);
    }

    pageModalActive.value = false;
  }

  function validatePageName(name: string, page: PageRow | null): string | null {
    const id = pageNameId(name);

    if (id === '') {
      return t('{attribute} cannot be blank.', {attribute: t('Page Name')});
    }

    const clash = pages.value.some(
      (p) => p !== page && pageNameId(p.name) === id
    );

    return clash ? t('Another page already has that name.') : null;
  }

  function pageActions(page: PageRow): ActionItem[] {
    return [
      {label: t('Page settings'), onClick: () => openPageSettings(page)},
      // Removing the last page would leave its sources homeless.
      ...(pages.value.length > 1
        ? [
            {
              label: t('Remove page'),
              variant: 'danger',
              onClick: () => removePage(page),
            } satisfies ActionItem,
          ]
        : []),
    ];
  }

  /** The other pages a source can move to, offered in its reorder menu. */
  function sourceMoves(source: SourceRow): ReorderMove[] {
    if (!source.key) return [];

    return pages.value
      .filter((page) => page.name !== source.page)
      .map((page) => ({
        value: page.name,
        label: t('Move to {page}', {page: page.name}),
        icon: page.icon,
      }));
  }

  function reorderPages(from: number, to: number): void {
    const [moved] = pages.value.splice(from, 1);
    if (moved) pages.value.splice(to, 0, moved);
  }

  /** Adds a page, leaving the one being viewed selected, as Craft 5 did. */
  function addPage(name: string, icon: string | null): void {
    pages.value.push({name, icon});
    announce(t('Success'));
  }

  function updatePage(page: PageRow, name: string, icon: string | null): void {
    const previous = page.name;
    page.icon = icon;
    page.name = name;

    // A page's name is its identity, so every source on it has to follow.
    for (const source of sources.value) {
      if (source.page === previous) source.page = name;
    }

    if (selectedPage.value === previous) selectedPage.value = name;
  }

  /**
   * Removes a page once confirmed. Its sources go to the end of the page before
   * it (or after, for the first) — Craft 5's behavior.
   */
  function removePage(page: PageRow): void {
    if (
      !window.confirm(
        t('Are you sure you want to remove the page “{name}”?', {
          name: page.name,
        })
      )
    ) {
      return;
    }

    const index = pages.value.indexOf(page);
    if (index === -1) return;

    const neighbor = pages.value[index - 1] ?? pages.value[index + 1];
    pages.value.splice(index, 1);

    if (neighbor) {
      moveRows(
        sources.value.filter((source) => source.page === page.name),
        neighbor.name
      );
    }

    onRemoved(page, neighbor);
  }

  return {
    pages,
    editingPage,
    pageModalActive,
    resetPages,
    openPageSettings,
    savePage,
    validatePageName,
    pageActions,
    sourceMoves,
    reorderPages,
  };
}
