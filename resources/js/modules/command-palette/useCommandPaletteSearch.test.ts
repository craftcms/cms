import {actionClient} from '@craftcms/ui';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {effectScope, nextTick, ref, shallowRef} from 'vue';
import type {CommandPaletteProvider} from './types';
import {
  SEARCH_DEBOUNCE,
  useCommandPaletteSearch,
} from './useCommandPaletteSearch';

vi.mock('@actions/CommandPaletteController', () => ({
  search: {url: ({provider}: {provider: string}) => `/palette/${provider}`},
}));

function serverItem(id: string, label: string) {
  return {
    id,
    label,
    description: null,
    icon: null,
    iconSvg: null,
    thumbHtml: null,
    href: `/${id}`,
    external: false,
    action: null,
    view: null,
    reload: false,
    actions: [],
    keywords: null,
    searchOnly: false,
    variant: null,
  };
}

describe('useCommandPaletteSearch', () => {
  const get = vi.spyOn(actionClient, 'get');
  const scope = effectScope();

  beforeEach(() => vi.useFakeTimers());
  afterEach(() => {
    vi.useRealTimers();
    get.mockReset();
  });

  function setUp(providers: CommandPaletteProvider[]) {
    const query = ref('');
    const search = scope.run(() =>
      useCommandPaletteSearch(query, shallowRef(providers), () => 'de')
    )!;

    return {query, search};
  }

  async function settle(): Promise<void> {
    await vi.advanceTimersByTimeAsync(SEARCH_DEBOUNCE);
    await nextTick();
  }

  it('searches again once the server’s providers arrive', async () => {
    get.mockResolvedValue({
      data: {results: [serverItem('sites', 'Site settings')]},
    } as never);
    const query = ref('site');
    const providers = shallowRef<CommandPaletteProvider[]>([]);
    const search = scope.run(() =>
      useCommandPaletteSearch(query, providers, () => null)
    )!;
    query.value = 'sites';
    await settle();

    providers.value = [{handle: 'pages', label: 'Pages'}];
    await settle();

    expect(search.results.value.map(({label}) => label)).toEqual([
      'Site settings',
    ]);
  });

  it('lists the current page’s results first, however closely others match', async () => {
    get.mockResolvedValue({
      data: {results: [serverItem('sites', 'Sites')]},
    } as never);
    const {query, search} = setUp([
      {handle: 'pages', label: 'Pages'},
      {
        handle: 'on-this-page',
        label: 'On This Page',
        thisPage: true,
        search: () => [{id: 'field', label: 'Site settings'}],
      },
    ]);

    query.value = 'sites';
    await settle();

    expect(search.results.value.map(({id}) => id)).toEqual(['field', 'sites']);
  });

  it('lists the page’s own results without waiting for the server', async () => {
    get.mockReturnValue(new Promise(() => {}) as never);
    const {query, search} = setUp([
      {
        handle: 'on-this-page',
        label: 'On This Page',
        thisPage: true,
        search: () => [{id: 'heading', label: 'Settings'}],
      },
      {handle: 'pages', label: 'Pages'},
    ]);

    query.value = 'settings';
    await vi.advanceTimersByTimeAsync(0);

    expect(search.results.value.map(({id}) => id)).toEqual(['heading']);
    expect(get).not.toHaveBeenCalled();

    await settle();

    expect(get).toHaveBeenCalledOnce();
    expect(search.searching.value).toBe(true);
  });

  it('merges every provider’s results into one ranked list', async () => {
    get.mockResolvedValue({
      data: {results: [serverItem('sites', 'Site settings')]},
    } as never);
    const {query, search} = setUp([
      {
        handle: 'on-this-page',
        label: 'On This Page',
        search: () => [{id: 'heading', label: 'Settings'}],
      },
      {handle: 'pages', label: 'Pages'},
    ]);

    query.value = 'settings';
    await settle();

    expect(get).toHaveBeenCalledWith('/palette/pages', {
      params: {query: 'settings', site: 'de'},
      signal: expect.any(AbortSignal),
    });
    expect(search.results.value.map(({id}) => id)).toEqual([
      'heading',
      'sites',
    ]);
    expect(search.searching.value).toBe(false);
  });

  it('drops results for a query that has since changed', async () => {
    let resolveStale!: (value: unknown) => void;
    get
      .mockReturnValueOnce(
        new Promise((resolve) => (resolveStale = resolve)) as never
      )
      .mockResolvedValueOnce({
        data: {results: [serverItem('fresh', 'Fresh')]},
      } as never);
    const {query, search} = setUp([{handle: 'pages', label: 'Pages'}]);

    query.value = 'fr';
    await settle();
    query.value = 'fresh';
    await settle();
    resolveStale({data: {results: [serverItem('stale', 'Fresh stale')]}});
    await nextTick();

    expect(search.results.value.map(({id}) => id)).toEqual(['fresh']);
  });

  it('keeps showing a provider’s results that still match while it searches again', async () => {
    get
      .mockResolvedValueOnce({
        data: {
          results: [
            serverItem('self', 'Self-Portrait'),
            serverItem('seller', 'Seller'),
          ],
        },
      } as never)
      .mockReturnValueOnce(new Promise(() => {}) as never);
    const {query, search} = setUp([{handle: 'pages', label: 'Pages'}]);

    query.value = 'sel';
    await settle();
    query.value = 'self';
    await settle();

    expect(search.searching.value).toBe(true);
    expect(search.results.value.map(({id}) => id)).toEqual(['self']);
  });

  it('reports providers whose searches failed', async () => {
    vi.spyOn(console, 'error').mockImplementation(() => {});
    get.mockRejectedValue(new Error('Nope'));
    const {query, search} = setUp([{handle: 'pages', label: 'Pages'}]);

    query.value = 'x';
    await settle();

    expect(search.failed.value).toEqual(['Pages']);
  });
});
