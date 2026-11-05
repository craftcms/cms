import {actionClient} from '@craftcms/ui';
import {search as searchUrl} from '@actions/CommandPaletteController';
import {computed, shallowRef, watch, type Ref} from 'vue';
import {matchesWords, rankItems} from './rank';
import {pageKey} from './recents';
import {
  fromServerItem,
  type CommandPaletteItem,
  type CommandPaletteProvider,
} from './types';

/** How long typing has to pause before the server's providers are asked. */
export const SEARCH_DEBOUNCE = 150;

/**
 * A provider still loading keeps the items it found for the previous query
 * that still match, so the list doesn't empty out on every keystroke while it
 * searches again, nor show what's since stopped matching.
 */
type ProviderState =
  | {status: 'loading'; items: CommandPaletteItem[]}
  | {status: 'done'; items: CommandPaletteItem[]}
  | {status: 'error'};

/**
 * Asks every provider for results as the query changes, each in its own
 * request, and merges whatever has come back into one ranked list.
 *
 * Results from a query that's since changed are dropped, and its requests
 * aborted, so a slow provider can't overwrite newer results.
 */
export function useCommandPaletteSearch(
  query: Ref<string>,
  providers: Ref<readonly CommandPaletteProvider[]>,
  site: () => string | null
) {
  const states = shallowRef(new Map<string, ProviderState>());
  let generation = 0;
  let controller: AbortController | null = null;
  let timer: ReturnType<typeof setTimeout> | undefined;

  function setState(handle: string, state: ProviderState): void {
    const next = new Map(states.value);
    next.set(handle, state);
    states.value = next;
  }

  async function searchProvider(
    provider: CommandPaletteProvider,
    term: string,
    signal: AbortSignal
  ): Promise<CommandPaletteItem[]> {
    if (provider.search) {
      return provider.search(term);
    }

    const {data} = await actionClient.get<{
      results: CraftCms.Cms.Cp.CommandPalette.CommandPaletteItem[];
    }>(searchUrl.url({provider: provider.handle}), {
      params: {query: term, ...(site() ? {site: site()} : {})},
      signal,
    });

    // The page being viewed isn't somewhere to go — nor are its drafts and
    // revisions, which share its path. The page's own actions offer those.
    const currentPage = pageKey(window.location.href);

    return data.results
      .map(fromServerItem)
      .filter((item) => !item.href || pageKey(item.href) !== currentPage);
  }

  function searchWith(
    list: readonly CommandPaletteProvider[],
    term: string,
    current: number,
    signal: AbortSignal
  ): void {
    for (const provider of list) {
      Promise.resolve()
        .then(() => searchProvider(provider, term, signal))
        .then(
          (items) => {
            if (current === generation) {
              setState(provider.handle, {status: 'done', items});
            }
          },
          (error) => {
            if (current === generation && !signal.aborted) {
              console.error(
                `The “${provider.label}” command palette search failed:`,
                error
              );
              setState(provider.handle, {status: 'error'});
            }
          }
        );
    }
  }

  /**
   * Searches for `term`. The providers that search in the browser — the
   * page's own — go straight away, so their results show while the server
   * is still being asked; with `debounce`, the server's wait for a pause in
   * typing first.
   */
  function run(term: string, {debounce = false} = {}): void {
    const current = ++generation;
    controller?.abort();
    controller = new AbortController();
    const {signal} = controller;

    states.value = new Map(
      providers.value.map(({handle}) => {
        const previous = states.value.get(handle);

        return [
          handle,
          {
            status: 'loading',
            items:
              previous && 'items' in previous
                ? previous.items.filter((item) => matchesWords(item, term))
                : [],
          },
        ];
      })
    );

    const inBrowser = providers.value.filter(({search}) => search);
    const onServer = providers.value.filter(({search}) => !search);

    searchWith(inBrowser, term, current, signal);

    if (debounce) {
      timer = setTimeout(
        () => searchWith(onServer, term, current, signal),
        SEARCH_DEBOUNCE
      );
    } else {
      searchWith(onServer, term, current, signal);
    }
  }

  function reset(): void {
    clearTimeout(timer);
    generation++;
    controller?.abort();
    controller = null;
    states.value = new Map();
  }

  watch(query, (value) => {
    clearTimeout(timer);
    const term = value.trim();

    if (!term) {
      reset();

      return;
    }

    run(term, {debounce: true});
  });

  // The server's providers arrive after the palette opens, so a query typed
  // straight away is searched again once they're known.
  watch(providers, () => {
    const term = query.value.trim();

    if (term) {
      clearTimeout(timer);
      run(term);
    }
  });

  /** The results of the providers matching `filter`, ranked against each other. */
  function rankedResults(
    filter: (provider: CommandPaletteProvider) => boolean
  ): CommandPaletteItem[] {
    return rankItems(
      providers.value.filter(filter).flatMap(({handle}) => {
        const state = states.value.get(handle);

        return state && 'items' in state ? state.items : [];
      }),
      query.value
    );
  }

  // Some providers' results come first, such as the page being viewed: what's
  // on it is what's most likely meant, even when something elsewhere matches
  // more closely.
  const results = computed(() => [
    ...rankedResults((provider) => Boolean(provider.displayFirst)),
    ...rankedResults((provider) => !provider.displayFirst),
  ]);

  /** Whether any provider is still working on the current query. */
  const searching = computed(
    () =>
      (query.value.trim() !== '' && states.value.size === 0) ||
      [...states.value.values()].some((state) => state.status === 'loading')
  );

  /** The providers whose searches failed, by label. */
  const failed = computed(() =>
    providers.value
      .filter(({handle}) => states.value.get(handle)?.status === 'error')
      .map(({label}) => label)
  );

  return {results, searching, failed, reset};
}
