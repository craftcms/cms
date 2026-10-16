import {actionClient} from '@craftcms/ui';
import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import FieldLayoutDesignerControl from './FieldLayoutDesignerControl.vue';
import type {UiPayload} from './types';

const html = `
  <div class="fld-cvd">
    <craft-tabs>
      <craft-tab slot="tab" data-fld-tab="content">Content Tabs</craft-tab>
      <div slot="panel"></div>
      <craft-tab slot="tab" data-fld-tab="cards">Cards</craft-tab>
      <div slot="panel"></div>
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

  expect(icon('content')?.getAttribute('name')).toBe('circle-exclamation');
  expect(icon('content')?.getAttribute('slot')).toBe('suffix');
  expect(icon('cards')).toBeNull();

  errors.value = [
    {
      path: ['fieldLayout'],
      messages: ['Invalid card attribute.'],
      source: ['fieldLayout', 'cardView'],
    },
  ];
  await flush();

  expect(icon('content')).toBeNull();
  expect(icon('cards')).not.toBeNull();

  errors.value = [
    {
      path: ['fieldLayout'],
      messages: ['Taken.'],
      source: ['fieldLayout', 'generatedFields', 'abc', 'handle'],
    },
  ];
  await flush();

  expect(icon('content')).toBeNull();
  expect(icon('cards')).toBeNull();
});
