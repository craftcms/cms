import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {createApp, defineComponent, h, type App} from 'vue';
import {useDashboard} from './useDashboard';
import {useFlashMessages} from '@/common/composables/useFlashMessages';
import type {DashboardWidget} from './types';

const state = vi.hoisted(() => ({
  post: vi.fn(),
  reload: vi.fn(),
}));
vi.mock('@craftcms/ui', () => ({
  actionClient: {post: state.post},
  t: (message: string) => message,
}));
vi.mock('@inertiajs/vue3', () => ({router: {reload: state.reload}}));
vi.mock('@/common/utils/jquery', () => ({
  jq: () => () => ({children: () => ({each: vi.fn()}), data: vi.fn()}),
}));
vi.mock('@/modules/grid/grid', () => ({
  Grid: class {
    $container = {height: vi.fn()};
    $items = {each: vi.fn()};
    items = [];
    totalCols = 4;
    setItems() {}
    refreshCols() {}
    destroy() {}
  },
}));

let app: App;
let host: HTMLElement;
let dashboard: ReturnType<typeof useDashboard>;

function widget(id: number): DashboardWidget {
  return {
    id,
    type: 'Example',
    colspan: 1,
    maxColspan: 4,
    title: `Widget ${id}`,
    subtitle: null,
    name: 'Example',
    settings: {limit: id},
    settingsForm: null,
    component: null,
    data: null,
    fragment: {html: '', headHtml: '', bodyHtml: ''},
  };
}

function mount(widgets: DashboardWidget[]) {
  host = document.createElement('div');
  document.body.append(host);
  app = createApp(
    defineComponent({
      setup() {
        dashboard = useDashboard({widgets, widgetTypes: {}});
        return () => h('div', {ref: dashboard.container});
      },
    })
  );
  app.mount(host);
}

beforeEach(() => {
  state.post.mockReset();
  state.reload.mockClear();
  useFlashMessages().clearAll();
});

afterEach(() => {
  app?.unmount();
  host?.remove();
});

it('keeps the saved layout when resizing or reordering fails', async () => {
  mount([widget(1), widget(2)]);
  state.post.mockRejectedValue(new Error('offline'));

  await dashboard.resize(dashboard.widgets.value[0]!, 3);
  expect(useFlashMessages().messages.value.error).toBe('Couldn’t save widget.');

  await dashboard.reorder(0, 1);

  expect(
    dashboard.widgets.value.map((item) => [item.id, item.colspan])
  ).toEqual([
    [1, 1],
    [2, 1],
  ]);
  expect(useFlashMessages().messages.value.error).toBe(
    'Couldn’t reorder widgets.'
  );
});

it('undo restores a deleted widget at the end', async () => {
  mount([widget(1), widget(2)]);
  state.post.mockResolvedValueOnce({data: {}});
  await dashboard.remove(dashboard.widgets.value[0]!);

  expect(dashboard.widgets.value.map((item) => item.id)).toEqual([2]);

  state.post.mockResolvedValueOnce({data: {info: widget(3)}});
  await dashboard.undo();

  expect(dashboard.widgets.value.map((item) => item.id)).toEqual([2, 3]);
  expect(dashboard.deleted.value).toBeUndefined();
});

it('keeps a widget and its undo state unchanged when deletion fails', async () => {
  mount([widget(1)]);
  state.post.mockRejectedValue(new Error('cancelled'));

  await dashboard.remove(dashboard.widgets.value[0]!);

  expect(dashboard.widgets.value.map((item) => item.id)).toEqual([1]);
  expect(dashboard.deleted.value).toBeUndefined();
});
