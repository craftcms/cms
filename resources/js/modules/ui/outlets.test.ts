import {createApp, h, nextTick} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import GroupNode from './GroupNode.vue';
import {registerOutlets} from './outlets';
import type {UiNodePayload} from './types';

let app: ReturnType<typeof createApp> | undefined;

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
});

function mountGroup(outlet?: string): HTMLElement {
  const container = document.createElement('div');
  document.body.append(container);
  const node = {
    uid: 'display-settings',
    type: 'CraftCms\\Cms\\Ui\\Nodes\\Group',
    component: 'craft:group',
    props: outlet ? {outlet} : {},
    children: [],
  } as unknown as UiNodePayload;

  app = createApp({
    render: () =>
      h(GroupNode, {
        node,
        values: {},
        errors: [],
        touchedPaths: new Set<string>(),
        scope: [],
        refreshable: false,
      }),
  });
  app.mount(container);

  return container;
}

it('renders a group in its outlet, and in place without one', async () => {
  const container = mountGroup('field-layout-designer.display');
  await nextTick();
  const group = () =>
    document.querySelector('[data-ui-node="display-settings"]');

  expect(container.contains(group())).toBe(true);

  const host = document.createElement('div');
  host.innerHTML = '<div data-ui-outlet="field-layout-designer.display"></div>';
  document.body.append(host);
  const release = registerOutlets(host);
  await nextTick();

  expect(host.querySelector('[data-ui-outlet]')!.contains(group())).toBe(true);

  release();
  await nextTick();

  expect(container.contains(group())).toBe(true);
});

it('leaves groups without an outlet in place', async () => {
  const container = mountGroup();
  const host = document.createElement('div');
  host.innerHTML = '<div data-ui-outlet="field-layout-designer.display"></div>';
  document.body.append(host);
  registerOutlets(host);
  await nextTick();

  expect(
    container.querySelector('[data-ui-node="display-settings"]')
  ).not.toBeNull();
});
