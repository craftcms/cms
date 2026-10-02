import {createApp, h, nextTick, shallowReactive, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import type {ContentIndexData} from '@/modules/elements/index/composables/useContentIndexData';
import {appendIndexQuery} from '@/modules/elements/index/composables/useElementIndexVisits';

const inertia = vi.hoisted(() => ({
  visit: vi.fn(),
  props: {} as ContentIndexData,
}));

vi.mock('@craftcms/ui', () => ({
  ButtonVariant: {Plain: 'plain'},
  t: (message: string) => message,
  Appearance: {Fill: 'fill'},
  actionClient: {post: vi.fn()},
}));
vi.mock('@inertiajs/vue3', async (importOriginal) => {
  const actual = await importOriginal<typeof import('@inertiajs/vue3')>();

  return {
    router: {
      visit: inertia.visit,
      on: actual.router.on.bind(actual.router),
    },
    usePage: () => ({props: inertia.props}),
  };
});
vi.mock('@/modules/elements/composables/useElementQuickEdit', () => ({
  useElementQuickEdit: () => ({}),
}));
vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({
    currentUser: {value: null},
    allowAdminChanges: {value: true},
  }),
}));
vi.mock('@/common/components/LayoutSlot.vue', () => ({
  default: {render: () => null},
}));
vi.mock('@/modules/elements/index/components/ElementTable.vue', () => ({
  default: {render: () => null},
}));
vi.mock('@/modules/elements/components/ElementCards.vue', () => ({
  default: {render: () => null},
}));
vi.mock('@/modules/elements/components/ElementThumbs.vue', () => ({
  default: {render: () => null},
}));
vi.mock(
  '@/modules/elements/index/components/customize-sources/CustomizeSourcesModal.vue',
  () => ({default: {render: () => null}})
);
vi.mock('@/modules/elements/index/components/IndexViewSettings.vue', () => ({
  default: {render: () => null},
}));
vi.mock('@craftcms/ui/vue/CraftSelectRich.vue', () => ({
  default: {render: () => null},
}));
vi.mock('@/modules/elements/index/components/FilterHud.vue', () => ({
  default: {render: () => null},
}));
vi.mock('@/modules/elements/ElementStatus.vue', () => ({
  default: {render: () => null},
}));
vi.mock('@craftcms/ui/vue/CraftInput.vue', () => ({
  default: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    setup:
      (
        props: {modelValue: string},
        {emit}: {emit: (name: string, value: string) => void}
      ) =>
      () =>
        h('input', {
          value: props.modelValue,
          onInput: (event: Event) =>
            emit('update:modelValue', (event.target as HTMLInputElement).value),
        }),
  },
}));

vi.mock('@/common/components/PaginationControls.vue', () => ({
  default: {render: () => null},
}));

const {default: ElementIndexPage} = await import('./ElementIndexPage.vue');
let app: App;
let container: HTMLElement;

async function mountPage() {
  vi.useFakeTimers();
  vi.stubGlobal('Craft', {systemUid: 'test', pageTrigger: 'p'});
  window.history.replaceState(
    {},
    '',
    '/admin/entries?p=3&site=default&sort[0][field]=title&sort[0][direction]=asc'
  );
  inertia.props = shallowReactive({
    elementType: 'Entry',
    elementDisplayName: 'Entry',
    context: 'index',
    source: {type: 'native', key: 'section:news', label: 'News'},
    sources: [],
    search: '',
    status: '',
    currentCondition: {
      class: 'EntryCondition',
      conditionRules: [{class: 'TitleRule', value: 'Alpha'}],
    },
    viewState: {mode: 'table'},
    viewModes: [{mode: 'table', title: 'Table', icon: 'table'}],
    tableColumns: [],
    defaultTableColumns: [],
    sort: [{field: 'title', direction: 'asc'}],
    sortOptions: [],
    data: [],
    pagination: {total: 0, per_page: 50, current_page: 3, last_page: 3},
    structure: null,
  } as unknown as ContentIndexData);
  container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () =>
      h(ElementIndexPage, {
        route: {url: (query = {}) => appendIndexQuery('/admin/entries', query)},
        filterParams: {includeSubfolders: 1},
      }),
  });
  app.mount(container);
  await nextTick();

  return container.querySelector('input')!;
}

async function type(input: HTMLInputElement, value: string) {
  input.value = value;
  input.dispatchEvent(new Event('input', {bubbles: true}));
  await nextTick();
}

afterEach(() => {
  app?.unmount();
  container?.remove();
  localStorage.clear();
  vi.useRealTimers();
  vi.unstubAllGlobals();
  inertia.visit.mockReset();
  window.history.replaceState({}, '', '/');
});

it.each([
  ['typing', true],
  ['Enter', false],
])('visits with indexed filter arrays after %s', async (_, replace) => {
  const input = await mountPage();
  input.focus();
  await type(input, 'n');
  await type(input, 'needle');
  input.setSelectionRange(3, 3);
  await vi.advanceTimersByTimeAsync(499);

  expect(inertia.visit).not.toHaveBeenCalled();

  if (replace) {
    await vi.advanceTimersByTimeAsync(1);
  } else {
    container
      .querySelector('form')!
      .dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
  }

  expect(inertia.visit).toHaveBeenCalledOnce();
  const [url, options] = inertia.visit.mock.calls[0]!;
  expect(
    Object.fromEntries(new URL(url, window.location.origin).searchParams)
  ).toEqual({
    site: 'default',
    source: 'section:news',
    viewMode: 'table',
    includeSubfolders: '1',
    search: 'needle',
    'condition[class]': 'EntryCondition',
    'condition[conditionRules][0][class]': 'TitleRule',
    'condition[conditionRules][0][value]': 'Alpha',
    'sort[0][field]': 'score',
    'sort[0][direction]': 'desc',
  });
  expect(options).toMatchObject({
    only: [],
    replace,
    preserveState: true,
    preserveScroll: true,
  });
  await vi.advanceTimersByTimeAsync(500);
  expect(inertia.visit).toHaveBeenCalledOnce();
  expect(document.activeElement).toBe(input);
  expect(input.selectionStart).toBe(3);
});

it('restores the preferred sort when a search is cleared before its response', async () => {
  const input = await mountPage();
  window.history.replaceState({}, '', '/admin/entries?p=3&site=default');
  await type(input, 'needle');
  await vi.advanceTimersByTimeAsync(500);
  await type(input, '');
  await vi.advanceTimersByTimeAsync(500);

  expect(inertia.visit).toHaveBeenCalledTimes(2);
  const [url] = inertia.visit.mock.calls[1]!;
  const query = new URL(url, window.location.origin).searchParams;
  expect(query.has('search')).toBe(false);
  expect(query.get('sort[0][field]')).toBe('title');
  expect(query.get('sort[0][direction]')).toBe('asc');
});

it.each([
  ['interrupted', false],
  ['failed', true],
])('retries search on Enter after a request is %s', async (_, completed) => {
  const input = await mountPage();
  await type(input, 'needle');
  await vi.advanceTimersByTimeAsync(500);
  const [, options] = inertia.visit.mock.calls[0]!;
  options.onFinish({completed});

  container
    .querySelector('form')!
    .dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));

  expect(inertia.visit).toHaveBeenCalledTimes(2);
  const [url, retryOptions] = inertia.visit.mock.calls[1]!;
  expect(new URL(url, window.location.origin).searchParams.get('search')).toBe(
    'needle'
  );
  expect(retryOptions.replace).toBe(false);
});

it.each([
  ['prefetch', true, false, true],
  ['background reload', false, true, true],
  ['navigation using a prefetch', false, false, false],
])('handles pending search during %s', async (_, prefetch, async, searches) => {
  const input = await mountPage();
  await type(input, 'needle');
  document.dispatchEvent(
    new CustomEvent('inertia:before', {
      detail: {visit: {prefetch, async}},
    })
  );
  await vi.advanceTimersByTimeAsync(500);

  expect(inertia.visit).toHaveBeenCalledTimes(searches ? 1 : 0);
});
