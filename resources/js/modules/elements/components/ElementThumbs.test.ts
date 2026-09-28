import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import ElementThumbs from './ElementThumbs.vue';
import {useSelectable} from '@/common/composables/useSelectable';

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {readOnly: false}}),
}));

describe('ElementThumbs', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  const tiles = [
    {id: 5, label: 'Homepage'},
    {id: 6, label: 'Untitled entry'},
  ];

  beforeEach(() => {
    // `craft-icon`s fetch their SVGs; left in flight they're aborted at
    // teardown and reported as unhandled errors.
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('<svg></svg>'))
    );
  });

  afterEach(() => {
    app?.unmount();
    container?.remove();
    vi.unstubAllGlobals();
  });

  function mount(props: Record<string, unknown> = {}) {
    const data = (props.data as typeof tiles | undefined) ?? tiles;
    const selection = useSelectable<number>({ids: data.map((tile) => tile.id)});

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(ElementThumbs, {data, selection, selectable: true, ...props} as any),
    });
    app.mount(container);

    return {root: container, selection};
  }

  it("labels each tile's checkbox with that tile's own title", async () => {
    const {root} = mount();
    await nextTick();

    const labels = [
      ...root.querySelectorAll('li craft-checkbox label[slot="label"]'),
    ].map((el) => el.textContent);

    expect(labels).toEqual(['Select Homepage', 'Select Untitled entry']);
  });
});
