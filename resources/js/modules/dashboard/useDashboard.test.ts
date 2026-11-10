import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {
  createApp,
  defineComponent,
  h,
  nextTick,
  onBeforeUnmount,
  reactive,
  type App,
} from 'vue';
import {useAnnouncer} from '@/common/composables/useAnnouncer';
import {broadcast} from '@/common/craft/broadcast';
import {useDashboard} from './useDashboard';
import type {DashboardWidget} from './types';

const state = vi.hoisted(() => ({
  post: vi.fn(),
  reload: vi.fn(),
}));
vi.mock('@craftcms/ui', () => ({
  actionClient: {post: state.post},
  t: (message: string, params: Record<string, unknown> = {}) =>
    message.replace(/\{(\w+)\}/g, (_, key: string) => String(params[key])),
}));
const messages = vi.hoisted(() => ({
  showMessage: vi.fn(),
  notice: vi.fn(),
  success: vi.fn(),
  error: vi.fn(),
}));
vi.mock('@/modules/messages/useMessages', () => ({
  useMessages: () => messages,
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
    settingsUi: null,
    component: null,
    data: null,
    fragment: {html: '', headHtml: '', bodyHtml: ''},
  };
}

/** Like a dashboard widget, it reports its settings closed as it goes. */
const WidgetFrame = defineComponent({
  props: {id: {type: Number, required: true}},
  setup(props, {slots}) {
    onBeforeUnmount(() => dashboard.settingsToggled(props.id, false));
    return () =>
      h('div', {class: 'dashboard-widget', 'data-id': props.id}, slots);
  },
});

function mount(widgets: DashboardWidget[]) {
  const props = reactive({widgets, widgetTypes: {}});
  host = document.createElement('div');
  document.body.append(host);
  app = createApp(
    defineComponent({
      setup() {
        dashboard = useDashboard(props);
        // A widget's content remounts when its data changes, as the
        // dashboard's does. One with `later` data gets it later, as HTML
        // widgets do.
        return () =>
          h(
            'div',
            {ref: dashboard.container},
            dashboard.widgets.value.map((item) =>
              h(
                WidgetFrame,
                {key: item.id, id: item.id},
                {
                  default: () => [
                    (item.data as {later?: boolean} | null)?.later
                      ? h('div', {key: 'later'})
                      : h(
                          'a',
                          {key: JSON.stringify(item.data), href: '#'},
                          item.title ?? ''
                        ),
                  ],
                }
              )
            )
          );
      },
    })
  );
  app.mount(host);
  return props;
}

beforeEach(() => {
  state.post.mockReset();
  state.reload.mockClear();
  Object.values(messages).forEach((fn) => fn.mockReset());
});

afterEach(() => {
  app?.unmount();
  host?.remove();
});

it('keeps the saved layout when resizing or reordering fails', async () => {
  mount([widget(1), widget(2)]);
  state.post.mockRejectedValue(new Error('offline'));

  await dashboard.resize(dashboard.widgets.value[0]!, 3);
  expect(messages.error).toHaveBeenLastCalledWith('Couldn’t save widget.');

  await dashboard.reorder(0, 1);

  expect(
    dashboard.widgets.value.map((item) => [item.id, item.colspan])
  ).toEqual([
    [1, 1],
    [2, 1],
  ]);
  expect(messages.error).toHaveBeenLastCalledWith('Couldn’t reorder widgets.');
});

it('announces where a widget moved once the new order is saved', async () => {
  mount([widget(1), widget(2), widget(3)]);
  state.post.mockResolvedValue({data: {}});

  await dashboard.reorder(2, 0);

  expect(useAnnouncer().announcement.value).toBe(
    'Moved Widget 3 to position 1'
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

it('reloads its widgets once elements are saved, after any open settings close', async () => {
  mount([widget(1), widget(2)]);
  dashboard.settingsToggled(2, true);

  broadcast({event: 'saveElement', id: 7});
  broadcast({event: 'saveElement', id: 8});
  await new Promise((resolve) => setTimeout(resolve, 100));

  expect(state.reload).not.toHaveBeenCalled();

  dashboard.settingsToggled(2, false);

  expect(state.reload).toHaveBeenCalledOnce();
  expect(state.reload).toHaveBeenCalledWith({only: ['widgets']});

  broadcast({event: 'saveElement', id: 7});
  broadcast({event: 'saveElement', id: 8});
  await vi.waitFor(() => expect(state.reload).toHaveBeenCalledTimes(2));
  await new Promise((resolve) => setTimeout(resolve, 100));

  expect(state.reload).toHaveBeenCalledTimes(2);
});

it('sets aside widgets reloaded while settings are open, and reloads them once they close', async () => {
  const props = mount([widget(1), widget(2)]);
  dashboard.settingsToggled(2, true);

  props.widgets = [{...widget(1), data: {entries: []}}, widget(2)];
  await nextTick();

  expect(dashboard.widgets.value[0]!.data).toBeNull();
  expect(state.reload).not.toHaveBeenCalled();

  dashboard.settingsToggled(2, false);

  expect(state.reload).toHaveBeenCalledWith({only: ['widgets']});
});

it('doesn’t reload a page it’s left with settings open after elements were saved', async () => {
  mount([widget(1), widget(2)]);
  dashboard.settingsToggled(2, true);
  broadcast({event: 'saveElement', id: 7});
  await new Promise((resolve) => setTimeout(resolve, 100));

  app.unmount();
  await Promise.resolve();

  expect(state.reload).not.toHaveBeenCalled();
});

it('keeps focus in a widget that remounts when it’s reloaded', async () => {
  const props = mount([widget(1), widget(2)]);
  await nextTick();
  host.querySelectorAll<HTMLElement>('a')[1]!.focus();

  props.widgets = [widget(1), {...widget(2), data: {entries: []}}];
  await new Promise((resolve) => setTimeout(resolve, 50));

  expect(document.activeElement?.textContent).toBe('Widget 2');
});

it('leaves focus alone when reloaded widgets come in another order', async () => {
  const props = mount([widget(1), widget(2), widget(3)]);
  await nextTick();
  host.querySelectorAll<HTMLElement>('a')[1]!.focus();

  props.widgets = [{...widget(2), data: {entries: []}}, widget(1), widget(3)];
  await new Promise((resolve) => setTimeout(resolve, 50));

  expect(document.activeElement).toBe(document.body);
});

it('keeps focus in a widget whose content comes in after it remounts', async () => {
  const props = mount([widget(1), widget(2), widget(3)]);
  await nextTick();
  host.querySelectorAll<HTMLElement>('a')[1]!.focus();

  props.widgets = [widget(1), {...widget(2), data: {later: true}}, widget(3)];
  await new Promise((resolve) => setTimeout(resolve, 50));

  expect(document.activeElement).toBe(document.body);

  const link = document.createElement('a');
  link.href = '#';
  link.textContent = 'Widget 2';
  host.querySelector('[data-id="2"] > div')!.append(link);
  await new Promise((resolve) => setTimeout(resolve, 50));

  expect(document.activeElement).toBe(link);
});
