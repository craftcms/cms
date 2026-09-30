import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {BulkActionItem} from '@/modules/elements/types/actions';

const BulkActionsBar = (await import('./ElementBulkActionsBar.vue')).default;
const apps: App[] = [];
const containers: HTMLElement[] = [];

beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => new Response('<svg></svg>'))
  );
});

afterEach(() => {
  apps.splice(0).forEach((app) => app.unmount());
  containers.splice(0).forEach((container) => container.remove());
  vi.unstubAllGlobals();
});

function mount(
  action: BulkActionItem,
  listeners: Record<string, (detail: unknown) => void> = {},
  props: Record<string, unknown> = {}
) {
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp({
    render: () =>
      h(BulkActionsBar, {
        selectedIds: [11],
        actions: [action],
        elementType: 'Entry',
        source: '*',
        context: 'index',
        ...listeners,
        ...props,
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  apps.push(app);
  containers.push(container);

  return container;
}

it('omits actions when any selected row lacks the required capability', async () => {
  const container = mount(
    {
      key: 'Delete',
      label: 'Delete',
      selectionAttribute: 'deletable',
      action: {type: 'http', url: '/delete'},
    },
    {},
    {
      selectedIds: [11, 12],
      selectedElements: [
        {id: 11, capabilities: {deletable: true}},
        {id: 12, capabilities: {deletable: false}},
      ],
    }
  );
  await nextTick();

  expect(container.querySelector('craft-action-item')).toBeNull();
});

it('copies the selected element with its site ID', async () => {
  const copyElements = vi.fn();
  vi.stubGlobal('Craft', {cp: {copyElements}, siteId: 1});
  const container = mount(
    {
      key: 'Copy',
      label: 'Copy',
      selectionAttribute: 'copyable',
      action: {type: 'event', name: 'craft:copy-elements'},
    },
    {},
    {selectedElements: [{id: 11, siteId: 2, capabilities: {copyable: true}}]}
  );
  await nextTick();

  window.dispatchEvent(
    new CustomEvent('craft:copy-elements', {
      detail: {
        trigger: container.querySelector('craft-action-item'),
        elementIds: [11],
        elementType: 'Entry',
      },
    })
  );

  expect(copyElements).toHaveBeenCalledWith([
    {type: 'Entry', id: 11, siteId: 2},
  ]);
});

it.each(['edit', 'view'] as const)(
  'emits %s only from the bulk bar that triggered it',
  async (type) => {
    const globalAction = vi.fn();
    const eventName = `craft:${type}-element`;
    window.addEventListener(eventName, globalAction);
    const firstAction = vi.fn();
    const secondAction = vi.fn();
    const action: BulkActionItem = {
      key: type,
      label: type,
      action: {type: 'event', name: `craft:${type}-element`},
    };
    const first = mount(action, {
      [type === 'edit' ? 'onEdit' : 'onView']: firstAction,
    });
    mount(action, {[type === 'edit' ? 'onEdit' : 'onView']: secondAction});
    await nextTick();
    first.querySelector('craft-action-item')!.click();

    expect(firstAction).toHaveBeenCalledExactlyOnceWith({
      elementIds: [11],
      elementType: 'Entry',
      trigger: first.querySelector('craft-button[slot="invoker"]'),
    });
    expect(secondAction).not.toHaveBeenCalled();
    window.removeEventListener(eventName, globalAction);
    expect(globalAction).not.toHaveBeenCalled();
  }
);
