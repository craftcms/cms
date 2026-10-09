import {createApp, h, nextTick, type App} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {BulkActionItem} from '@/modules/elements/types/actions';

const actions = vi.hoisted(() => ({
  run: vi.fn(async (..._args: unknown[]) => {}),
}));
vi.mock('@craftcms/ui/actions.mjs', () => ({runAction: actions.run}));

const BulkActionsBar = (await import('./ElementBulkActionsBar.vue')).default;
const apps: App[] = [];
const containers: HTMLElement[] = [];

beforeEach(() => {
  vi.stubGlobal('Craft', {cp: {copyElements: vi.fn()}, siteId: 1});
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => new Response('<svg></svg>'))
  );
});

afterEach(() => {
  apps.splice(0).forEach((app) => app.unmount());
  containers.splice(0).forEach((container) => container.remove());
  actions.run.mockClear();
  vi.unstubAllGlobals();
});

function mount(
  action: BulkActionItem,
  listeners: Record<string, (...args: any[]) => void> = {},
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

it('lets an embedded wrapper add owner parameters at execution time', async () => {
  const perform = vi.fn(async (_item, run) => run({ownerId: 73}));
  const container = mount(
    {
      key: 'plugin\\Download',
      label: 'Download',
      action: {
        type: 'download',
        method: 'POST',
        url: '/download',
        body: {format: 'zip'},
      },
    },
    {},
    {params: {ownerId: 31, ownerSiteId: 1}, perform}
  );
  await nextTick();

  container.querySelector<HTMLElement>('craft-action-item')!.click();
  await vi.waitFor(() => expect(actions.run).toHaveBeenCalledOnce());

  expect(actions.run.mock.calls[0]![0]).toEqual({
    type: 'download',
    method: 'POST',
    url: '/download',
    body: {
      format: 'zip',
      elementType: 'Entry',
      source: '*',
      context: 'index',
      elementIds: [11],
      ownerId: 73,
      ownerSiteId: 1,
    },
  });
});

it('copies the selected nested-element metadata', async () => {
  const container = mount(
    {
      key: 'Copy',
      label: 'Copy',
      selectionAttribute: 'copyable',
      action: {type: 'event', name: 'craft:copy-elements'},
    },
    {},
    {
      selectedElements: [
        {
          id: 11,
          siteId: 2,
          entryTypeId: 9,
          ownerId: 73,
          capabilities: {copyable: true},
          cardAttributes: {
            data: {
              'field-id': 7,
              'draft-id': 5,
              'revision-id': 3,
            },
          },
        },
      ],
    }
  );
  await nextTick();
  const trigger = container.querySelector<HTMLElement>('craft-action-item')!;

  window.dispatchEvent(
    new CustomEvent('craft:copy-elements', {
      detail: {trigger, elementIds: [11], elementType: 'Entry'},
    })
  );

  expect(window.Craft?.cp?.copyElements).toHaveBeenCalledWith([
    {
      type: 'Entry',
      id: 11,
      siteId: 2,
      ownerId: 73,
      fieldId: 7,
      draftId: 5,
      revisionId: 3,
      data: {entryTypeId: 9},
    },
  ]);
});

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
