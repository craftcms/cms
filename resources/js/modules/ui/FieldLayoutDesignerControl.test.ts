import {actionClient} from '@craftcms/ui';
import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import FieldLayoutDesignerControl from './FieldLayoutDesignerControl.vue';
import GroupNode from './GroupNode.vue';
import {useOutlet} from './outlets';
import type {UiPayload} from './types';

const html = `
  <div class="fld-cvd">
    <craft-tabs>
      <craft-tab slot="tab" data-fld-tab="content">Content</craft-tab>
      <div slot="panel"></div>
      <craft-tab slot="tab" data-fld-tab="appearance">Appearance</craft-tab>
      <div slot="panel"><div data-ui-outlet="field-layout-designer.appearance"></div></div>
    </craft-tabs>
  </div>`;

let app: ReturnType<typeof createApp> | undefined;

afterEach(() => {
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

const flush = async () => {
  for (let i = 0; i < 5; i++) {
    await Promise.resolve();
    await nextTick();
  }
};

it('flags the designer tabs that have errors', async () => {
  vi.spyOn(actionClient, 'post').mockResolvedValue({data: {html}} as never);
  const errors = ref<UiPayload['errors']>([
    {
      path: ['fieldLayout'],
      messages: ['Invalid condition.'],
      source: ['fieldLayout', 'tabs', '0', 'elements', '1', 'elementCondition'],
    },
  ]);
  const container = document.createElement('div');
  document.body.append(container);

  app = createApp({
    render: () =>
      h(FieldLayoutDesignerControl, {
        control: {
          path: ['fieldLayout'],
          props: {
            elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
            customizableTabs: true,
            withGeneratedFields: true,
            withCardViewDesigner: true,
          },
        } as never,
        value: {},
        editable: true,
        errors: errors.value,
      }),
  });
  app.mount(container);
  await flush();

  const icon = (tab: string) =>
    container.querySelector(
      `craft-tab[data-fld-tab="${tab}"] > [data-fld-tab-error]`
    );

  expect(icon('content')?.getAttribute('name')).toBe('triangle-exclamation');
  expect(icon('content')?.getAttribute('slot')).toBe('suffix');
  expect(icon('appearance')).toBeNull();

  errors.value = [
    {
      path: ['fieldLayout'],
      messages: ['Invalid card attribute.'],
      source: ['fieldLayout', 'cardView'],
    },
  ];
  await flush();

  expect(icon('content')).toBeNull();
  expect(icon('appearance')).not.toBeNull();

  errors.value = [
    {
      path: ['fieldLayout'],
      messages: ['Taken.'],
      source: ['fieldLayout', 'generatedFields', 'abc', 'handle'],
    },
  ];
  await flush();

  // Generated fields are in the Content tab.
  expect(icon('content')).not.toBeNull();
  expect(icon('appearance')).toBeNull();
});

it('provides the Appearance tab’s outlet while mounted', async () => {
  vi.spyOn(actionClient, 'post').mockResolvedValue({data: {html}} as never);
  const container = document.createElement('div');
  document.body.append(container);
  const outlet = useOutlet(() => 'field-layout-designer.appearance');

  app = createApp({
    render: () =>
      h(FieldLayoutDesignerControl, {
        control: {
          path: ['fieldLayout'],
          props: {
            elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
            customizableTabs: true,
            withGeneratedFields: true,
            withCardViewDesigner: true,
          },
        } as never,
        value: {},
        editable: true,
      }),
  });
  app.mount(container);
  await flush();

  expect(outlet.value).toBe(
    container.querySelector(
      '[data-ui-outlet="field-layout-designer.appearance"]'
    )
  );

  app.unmount();
  app = undefined;

  expect(outlet.value).toBeNull();
});

function mountGroupBeforeDesigner(withCardViewDesigner: boolean): HTMLElement {
  const container = document.createElement('div');
  document.body.append(container);

  app = createApp({
    render: () => [
      h('div', {class: 'in-place'}, [
        h(GroupNode, {
          node: {
            uid: 'display-settings',
            type: 'CraftCms\\Cms\\Ui\\Nodes\\Group',
            component: 'craft:group',
            props: {outlet: 'field-layout-designer.appearance'},
            children: [],
          } as never,
          values: {},
          errors: [],
          touchedPaths: new Set<string>(),
          scope: [],
          refreshable: false,
        }),
      ]),
      h(FieldLayoutDesignerControl, {
        control: {
          path: ['fieldLayout'],
          props: {
            elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
            customizableTabs: true,
            withGeneratedFields: true,
            withCardViewDesigner,
          },
        } as never,
        value: {},
        editable: true,
      }),
    ],
  });
  app.mount(container);

  return container;
}

it('holds groups bound for the Appearance tab until it renders', async () => {
  let resolve!: (value: unknown) => void;
  vi.spyOn(actionClient, 'post').mockReturnValue(
    new Promise((r) => (resolve = r)) as never
  );
  const container = mountGroupBeforeDesigner(true);
  await flush();

  expect(
    container.querySelector('[data-ui-node="display-settings"]')
  ).toBeNull();

  resolve({
    data: {
      html: '<div data-ui-outlet="field-layout-designer.appearance"></div>',
    },
  });
  await flush();

  expect(
    container.querySelector(
      '[data-ui-outlet="field-layout-designer.appearance"] [data-ui-node="display-settings"]'
    )
  ).not.toBeNull();
});

it('renders groups in place without a card view designer', async () => {
  vi.spyOn(actionClient, 'post').mockReturnValue(
    new Promise(() => {}) as never
  );
  const container = mountGroupBeforeDesigner(false);
  await flush();

  expect(
    container.querySelector('.in-place [data-ui-node="display-settings"]')
  ).not.toBeNull();
});
