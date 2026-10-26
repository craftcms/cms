import {createApp, h, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {
  type UseReorderableItemsReturn,
  useReorderableItems,
} from './useReorderableItems';

let app: ReturnType<typeof createApp> | undefined;

afterEach(() => {
  app?.unmount();
  document.body.innerHTML = '';
});

async function mountList(ids: string[]): Promise<UseReorderableItemsReturn> {
  let reorder!: UseReorderableItemsReturn;
  const container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    setup() {
      reorder = useReorderableItems({
        getItemIds: () => ids,
        onReorder: vi.fn(),
      });

      return () =>
        h(
          'ul',
          ids.map((id) =>
            h('li', {
              key: id,
              ref: (el) => reorder.setItemRef(el as Element, id),
            })
          )
        );
    },
  });
  app.mount(container);
  await nextTick();
  return reorder;
}

it('places a lone item as the only one', async () => {
  const reorder = await mountList(['solo']);

  expect(reorder.getRowPosition(0)).toBe('only');
});
