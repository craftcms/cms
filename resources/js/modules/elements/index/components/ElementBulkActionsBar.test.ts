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
  listeners: Record<string, (detail: unknown) => void> = {}
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
      }),
  });
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(container);
  apps.push(app);
  containers.push(container);

  return container;
}

it.each(['edit', 'view'] as const)(
  'emits %s only from the bulk bar that triggered it',
  async (type) => {
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

    expect(firstAction).toHaveBeenCalledWith({
      elementIds: [11],
      elementType: 'Entry',
      trigger: first.querySelector('craft-button[slot="invoker"]'),
    });
    expect(secondAction).not.toHaveBeenCalled();
  }
);
