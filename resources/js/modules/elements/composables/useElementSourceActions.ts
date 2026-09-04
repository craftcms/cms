import {computed, ref, watch, type MaybeRefOrGetter, toValue} from 'vue';
import {router} from '@inertiajs/vue3';
import useCraftData from '@/common/composables/useCraftData';
import type {
  ElementIndexRoute,
  IndexVisitor,
} from '@/modules/elements/composables/useElementIndexVisits';
import type {
  Source,
  SourceHeading,
  SourceItem,
} from '@/modules/elements/types/sources';
import type {
  ActionItemButton,
  ActionItemGroup,
  ActionItems,
} from '@/common/types';

export interface ElementSourceActionsOptions {
  sources: MaybeRefOrGetter<Array<Source>>;
  route: MaybeRefOrGetter<ElementIndexRoute>;
  activeSource?: MaybeRefOrGetter<string | null | undefined>;
  viewMode?: MaybeRefOrGetter<string | null | undefined>;
  /**
   * Supplied by indexes that aren't a page — the element selector modal.
   *
   * Without it, picking a source runs an Inertia visit, which in a modal
   * navigates the page *behind* it.
   */
  indexVisitor?: MaybeRefOrGetter<IndexVisitor | undefined>;
}

/**
 * An element index's sources, described as action items.
 *
 * Selecting a source isn't a navigation: it's a partial visit that leaves the
 * source list and the publishable sections alone and keeps the list's scroll
 * and state. So these are buttons carrying the behaviour rather than links,
 * which is also what lets the same descriptors render as a secondary nav on a
 * page and as a plain list inside the selector modal.
 */
export function useElementSourceActions(options: ElementSourceActionsOptions) {
  const {site} = useCraftData();

  /**
   * The source the user just clicked, activated before the round-trip so the
   * selection doesn't lag the pointer. Once the visit settles the server's
   * `activeSource` is authoritative again.
   */
  const pendingSource = ref<string | null>(null);

  const activeKey = computed(
    () => pendingSource.value ?? toValue(options.activeSource)
  );

  const visitOptions = {
    // Switching sources rebuilds the list view (data, columns, sort, actions,
    // pagination…), but the source nav itself and the publishable sections
    // behind the New-entry button don't change — so skip re-sending those two
    // rather than re-fetching the whole page.
    except: ['sources', 'publishableSections'],
    preserveState: true,
    preserveScroll: true,
  };

  // Carry the active view mode so the server renders data for the mode the
  // page is actually showing, rather than falling back to `table` while the
  // restored local view state still shows cards.
  function sourceUrl(key: string): string {
    return toValue(options.route).url({
      source: key,
      site: site?.handle,
      viewMode: toValue(options.viewMode) || undefined,
    });
  }

  function prefetchSource(key: string): void {
    // Prefetching is an Inertia notion; a non-page index fetches its own way.
    if (toValue(options.indexVisitor)) {
      return;
    }

    router.prefetch(sourceUrl(key), visitOptions, {cacheFor: 0});
  }

  function visitSource(key: string): void {
    if (key === activeKey.value) {
      return;
    }

    pendingSource.value = key;

    const visitor = toValue(options.indexVisitor);

    if (visitor) {
      visitor.merge(
        {source: key, viewMode: toValue(options.viewMode) || null},
        {resetPage: true}
      );

      return;
    }

    router.visit(sourceUrl(key), {
      ...visitOptions,
      onFinish: () => {
        // The key guard means a superseded (cancelled) visit from rapid
        // switching won't clear the highlight for a newer selection.
        if (pendingSource.value === key) {
          pendingSource.value = null;
        }
      },
    });
  }

  // A visitor-driven index has no `onFinish` to hook, so the optimistic
  // highlight is released when the server's active source catches up.
  watch(
    () => toValue(options.activeSource),
    (next) => {
      if (pendingSource.value !== null && next === pendingSource.value) {
        pendingSource.value = null;
      }
    }
  );

  /**
   * Folder and volume sources double as drag-and-drop move targets: their
   * `data` carries the backend `folder-id` / `can-move-to` flags. Inert unless
   * an asset-move drag is set up on the page.
   */
  function moveAttrs(source: SourceItem): Record<string, string | undefined> {
    const folderId = source.data?.['folder-id'];

    if (folderId == null || folderId === false) {
      return {};
    }

    return {
      'data-folder-drop-target': '',
      'data-folder-id': String(folderId),
      'data-can-move-to': source.data?.['can-move-to'] ? '' : undefined,
    };
  }

  function toAction(source: SourceItem): ActionItemButton {
    return {
      label: source.label,
      selected: source.key === activeKey.value,
      attrs: moveAttrs(source),
      onClick: () => visitSource(source.key),
      onMousedown: () => prefetchSource(source.key),
    };
  }

  /**
   * Each heading absorbs the sources that follow it, until the next one.
   * Sources before the first heading stay at the top level, as do the ones
   * under a blank heading — `ElementSources` emits one to separate a run
   * without labelling it.
   */
  const actions = computed<ActionItems>(() => {
    const result: ActionItems = [];
    let group: ActionItemGroup | null = null;

    for (const source of toValue(options.sources)) {
      if (source.type === 'heading') {
        group = source.heading
          ? {type: 'group', heading: source.heading, items: []}
          : null;

        if (group) {
          result.push(group);
        }

        // A heading carrying its own children arrives already grouped.
        for (const child of (source as SourceHeading).children ?? []) {
          (group?.items ?? result).push(toAction(child));
        }

        continue;
      }

      (group?.items ?? result).push(toAction(source));
    }

    return result;
  });

  return {actions, activeKey, sourceUrl, visitSource, prefetchSource};
}
