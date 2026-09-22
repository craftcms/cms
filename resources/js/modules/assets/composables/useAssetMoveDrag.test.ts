import {afterEach, expect, it} from 'vite-plus/test';
import {createApp, defineComponent, h} from 'vue';
import {useElementIndexTable} from '@/modules/elements/composables/useElementIndexTable';
import {useAssetMoveDrag} from './useAssetMoveDrag';

let app: ReturnType<typeof createApp> | null = null;
let container: HTMLElement | null = null;

afterEach(() => {
  app?.unmount();
  app = null;
  container?.remove();
  container = null;
  useElementIndexTable().register(null);
});

it('keeps asset links clickable without disabling row dragging', () => {
  useElementIndexTable().register(null);

  const Component = defineComponent({
    setup() {
      useAssetMoveDrag();

      return () =>
        h('div', {class: 'element-index'}, [
          h('div', {class: 'element-index__body'}, [
            h('div', {'data-movable-item': '', 'data-row-id': '42'}, [
              h('a', {href: '/admin/assets/edit/42-example'}, 'Example'),
              h('span', 'Filename'),
            ]),
          ]),
        ]);
    },
  });

  container = document.createElement('div');
  document.body.append(container);
  app = createApp(Component);
  app.mount(container);

  const linkEvent = new PointerEvent('pointerdown', {
    bubbles: true,
    cancelable: true,
    button: 0,
    isPrimary: true,
  });
  container.querySelector('a')!.dispatchEvent(linkEvent);

  const rowEvent = new PointerEvent('pointerdown', {
    bubbles: true,
    cancelable: true,
    button: 0,
    isPrimary: true,
  });
  container.querySelector('span')!.dispatchEvent(rowEvent);

  expect(linkEvent.defaultPrevented).toBe(false);
  expect(rowEvent.defaultPrevented).toBe(true);
});
