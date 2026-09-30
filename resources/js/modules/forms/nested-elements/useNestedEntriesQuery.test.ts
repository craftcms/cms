import {effectScope, nextTick, reactive, shallowRef} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import type {
  NestedContentIndexData,
  NestedEntriesProps,
} from './nested-entries';
import {useNestedEntriesQuery} from './useNestedEntriesQuery';

const ui = vi.hoisted(() => ({
  post: vi.fn(),
  appendHeadHtml: vi.fn(async () => {}),
  appendBodyHtml: vi.fn(async () => {}),
}));
const actions = vi.hoisted(() => ({run: vi.fn()}));

vi.mock('@craftcms/ui', async (original) => ({
  ...(await original<Record<string, unknown>>()),
  actionClient: {post: ui.post},
  appendHeadHtml: ui.appendHeadHtml,
  appendBodyHtml: ui.appendBodyHtml,
}));
vi.mock('@craftcms/ui/actions.mjs', () => ({runAction: actions.run}));

describe('useNestedEntriesQuery', () => {
  let scope: ReturnType<typeof effectScope>;

  afterEach(() => {
    scope?.stop();
    ui.post.mockReset();
    ui.appendHeadHtml.mockClear();
    ui.appendBodyHtml.mockClear();
    actions.run.mockReset();
    localStorage.clear();
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  function payload(
    overrides: Partial<NestedContentIndexData> = {}
  ): NestedContentIndexData {
    return {
      elementType: 'Entry',
      elementDisplayName: 'Entry',
      elementPluralDisplayName: 'Entries',
      canHaveDrafts: true,
      title: 'Entries',
      page: null,
      selectedSubnavItem: null,
      showSiteMenu: false,
      showStatusMenu: true,
      siteId: 2,
      sites: [],
      crumbs: [],
      structure: null,
      drafts: false,
      trashed: false,
      context: 'embeddedIndex',
      source: {type: 'native', key: '__IMP__', label: 'Entries'},
      sources: [],
      status: '',
      statusOptions: [{label: 'All', value: ''}],
      search: '',
      currentCondition: null,
      viewState: {mode: 'table', showHeaderColumn: true},
      viewModes: [
        {mode: 'cards', title: 'Cards', icon: 'grid'},
        {mode: 'table', title: 'Table', icon: 'table'},
      ],
      tableColumns: [
        {label: 'Title', value: 'title'},
        {label: 'Post date', value: 'postDate'},
        {label: 'Date updated', value: 'dateUpdated'},
      ],
      defaultTableColumns: ['postDate'],
      sort: [{field: 'sortOrder', direction: 'asc'}],
      sortOptions: [
        {label: 'Order', value: 'sortOrder', defaultDir: 'asc'},
        {label: 'Post date', value: 'postDate', defaultDir: 'desc'},
      ],
      data: [
        {
          id: 29,
          label: 'Entry 29',
          siteId: 2,
          editUrl: '/edit/29',
        } as NestedContentIndexData['data'][number],
      ],
      actions: null,
      exporters: [{type: 'Expanded', name: 'Expanded', formattable: true}],
      pagination: {
        total: 1,
        unfilteredTotal: 11,
        per_page: 7,
        current_page: 1,
        last_page: 1,
        next_page_url: null,
        prev_page_url: null,
        from: 1,
        to: 1,
      },
      reorderable: true,
      headHtml: '<style>.nested{}</style>',
      bodyHtml: '<script>window.nested = true;</script>',
      ...overrides,
    } as NestedContentIndexData;
  }

  function setup(
    options: {
      initial?: NestedContentIndexData;
    } = {}
  ) {
    vi.stubGlobal('Craft', {systemUid: 'test', pageTrigger: 'p'});
    const initial = options.initial ?? payload();
    const props = reactive({
      viewMode: 'index',
      manager: {
        ownerElementType: 'Entry',
        ownerId: 31,
        ownerSiteId: 2,
        attribute: 'field:articles',
        fieldId: 7,
        elementType: 'Entry',
        canCreate: true,
        canPaste: true,
        sortable: true,
        createAttributes: [{label: 'Article', attributes: {typeId: 9}}],
        pasteableEntryTypeIds: [9],
      },
      cards: [],
      index: {
        indexSettings: {
          storageKey: 'field:articles',
          showHeaderColumn: true,
          static: false,
        },
        initial,
      },
    }) as unknown as NestedEntriesProps;
    const busy = shallowRef(false);
    const error = shallowRef('');
    const onLoaded = vi.fn(async () => {});
    scope = effectScope();
    const state = scope.run(() =>
      useNestedEntriesQuery({
        props: () => props,
        busy,
        error,
        onLoaded,
        editable: () => true,
      })
    )!;

    return {...state, props, busy, error, onLoaded};
  }

  it('uses the initial payload without requesting when persisted state matches', async () => {
    localStorage.setItem(
      'Craft-test.nestedentries.field:articles',
      JSON.stringify({
        mode: 'table',
        sources: {
          __IMP__: {
            visible: ['postDate'],
            sort: [{field: 'sortOrder', direction: 'asc'}],
          },
        },
      })
    );
    const state = setup();
    await nextTick();

    expect(ui.post).not.toHaveBeenCalled();
    expect(state.entries.value.map(({id}) => id)).toEqual([29]);
    expect(ui.appendHeadHtml).toHaveBeenCalledOnce();
    expect(ui.appendHeadHtml).toHaveBeenCalledWith('<style>.nested{}</style>');
    expect(ui.appendBodyHtml).toHaveBeenCalledOnce();
  });

  it('makes one combined restore request when persisted state differs', async () => {
    localStorage.setItem(
      'Craft-test.nestedentries.field:articles',
      JSON.stringify({
        mode: 'cards',
        sources: {
          __IMP__: {
            visible: ['dateUpdated'],
            sort: [{field: 'postDate', direction: 'desc'}],
          },
        },
      })
    );
    ui.post.mockImplementation(async (_url, body) => ({
      data: payload({
        viewState: {mode: body.viewMode},
        defaultTableColumns: body.columns,
        sort: body.sort,
      }),
    }));

    const state = setup();
    await vi.waitFor(() => expect(state.loading.value).toBe(false));

    expect(ui.post).toHaveBeenCalledOnce();
    expect(ui.post.mock.calls[0]![1]).toEqual({
      ownerElementType: 'Entry',
      ownerId: 31,
      ownerSiteId: 2,
      attribute: 'field:articles',
      elementType: 'Entry',
      context: 'embeddedIndex',
      source: '__IMP__',
      viewMode: 'cards',
      columns: ['dateUpdated'],
      sort: [{field: 'postDate', direction: 'desc'}],
      showInGrid: true,
    });
    expect(state.mode.value).toBe('cards');
  });

  it('falls back to the first server-provided mode when a persisted mode is unavailable', async () => {
    localStorage.setItem(
      'Craft-test.nestedentries.field:articles',
      JSON.stringify({mode: 'table'})
    );
    const initial = payload({
      viewState: {mode: 'cards'},
      viewModes: [{mode: 'cards', title: 'Cards', icon: 'grid'}],
    });

    const state = setup({initial});
    await nextTick();

    expect(state.mode.value).toBe('cards');
    expect(ui.post).not.toHaveBeenCalled();
  });

  it('posts only owner scope, index query, and permitted runtime flags', async () => {
    ui.post.mockResolvedValue({data: payload()});
    const state = setup();

    state.status.value = 'disabled';
    await vi.waitFor(() => expect(ui.post).toHaveBeenCalledOnce());

    expect(ui.post.mock.calls[0]![1]).toEqual({
      ownerElementType: 'Entry',
      ownerId: 31,
      ownerSiteId: 2,
      attribute: 'field:articles',
      elementType: 'Entry',
      context: 'embeddedIndex',
      source: '__IMP__',
      viewMode: 'table',
      columns: ['postDate'],
      sort: [{field: 'sortOrder', direction: 'asc'}],
      status: 'disabled',
      showInGrid: false,
    });
    expect(ui.post.mock.calls[0]![1]).not.toHaveProperty('fieldId');
    expect(ui.post.mock.calls[0]![1]).not.toHaveProperty('per_page');
    expect(ui.post.mock.calls[0]![1]).not.toHaveProperty('fieldLayouts');

    state.props.manager!.ownerId = 73;
    await state.load(true);
    expect(ui.post.mock.lastCall![1]).toMatchObject({
      ownerId: 73,
      editable: true,
    });
    expect(ui.post.mock.lastCall![1]).not.toHaveProperty('prevalidate');

    state.props.manager!.prevalidate = true;
    await vi.waitFor(() =>
      expect(ui.post.mock.lastCall![1]).toMatchObject({prevalidate: true})
    );
  });

  it('hides initial rows while the selected mode awaits matching payload data', async () => {
    const pending = deferred<{data: NestedContentIndexData}>();
    ui.post.mockReturnValue(pending.promise);
    const state = setup();

    state.mode.value = 'cards';
    expect(state.entries.value).toEqual([]);

    pending.resolve({data: payload({viewState: {mode: 'cards'}})});
    await vi.waitFor(() => expect(state.loading.value).toBe(false));
    expect(state.entries.value.map(({id}) => id)).toEqual([29]);
  });

  it('surfaces request failures and applies response assets before notifying the owner', async () => {
    ui.post.mockRejectedValueOnce(new Error('Connection lost'));
    const state = setup();

    await state.load();
    expect(state.error.value).toBe('Connection lost');

    ui.appendHeadHtml.mockClear();
    ui.appendBodyHtml.mockClear();
    const headAssets = deferred<void>();
    const bodyAssets = deferred<void>();
    ui.appendHeadHtml.mockReturnValueOnce(headAssets.promise);
    ui.appendBodyHtml.mockReturnValueOnce(bodyAssets.promise);
    ui.post.mockResolvedValueOnce({
      data: payload({
        headHtml: '<style>.loaded{}</style>',
        bodyHtml: '<script>window.loaded = true;</script>',
      }),
    });

    const loaded = state.load();
    await vi.waitFor(() => expect(ui.appendHeadHtml).toHaveBeenCalledOnce());
    expect(ui.appendBodyHtml).not.toHaveBeenCalled();
    expect(state.onLoaded).not.toHaveBeenCalled();

    headAssets.resolve();
    await vi.waitFor(() => expect(ui.appendBodyHtml).toHaveBeenCalledOnce());
    expect(state.onLoaded).not.toHaveBeenCalled();

    bodyAssets.resolve();
    await loaded;

    expect(state.error.value).toBe('');
    expect(ui.appendHeadHtml).toHaveBeenCalledWith('<style>.loaded{}</style>');
    expect(ui.appendBodyHtml).toHaveBeenCalledWith(
      '<script>window.loaded = true;</script>'
    );
    expect(state.onLoaded).toHaveBeenCalledOnce();
  });
});

function deferred<Value>() {
  let resolve!: (value: Value) => void;
  let reject!: (reason?: unknown) => void;
  const promise = new Promise<Value>((success, failure) => {
    resolve = success;
    reject = failure;
  });

  return {promise, resolve, reject};
}
