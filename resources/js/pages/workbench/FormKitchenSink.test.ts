import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
import FormKitchenSink from './FormKitchenSink.vue';

let app: ReturnType<typeof createApp> | null = null;
let container: HTMLElement | null = null;

afterEach(() => {
  app?.unmount();
  container?.remove();
});

it('renders the renderer switch in the page actions', async () => {
  container = document.createElement('div');
  document.body.append(container);

  app = createApp(
    defineComponent({
      render: () =>
        h('div', [
          h(LayoutSlotOutlet, {name: 'actions'}),
          h(FormKitchenSink, {
            stories: {},
            additionalButtons:
              '<a href="/workbench/forms/icon-picker/html">HTML</a>',
          }),
        ]),
    })
  );
  app.mount(container);
  await nextTick();

  expect(
    container.querySelector<HTMLAnchorElement>('[data-layout-slot="actions"] a')
      ?.href
  ).toContain('/workbench/forms/icon-picker/html');
});
