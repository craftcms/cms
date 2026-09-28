import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import ElementCards from './ElementCards.vue';
import {useSelectable} from '@/common/composables/useSelectable';

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => ({props: {readOnly: false}}),
}));

describe('ElementCards', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  const cards = [
    {
      id: 5,
      label: 'Homepage',
      cardHeaderHtml: '',
      cardContentHtml: '',
      cardFooterHtml: '',
    },
    {
      id: 6,
      label: 'Untitled entry',
      cardHeaderHtml: '',
      cardContentHtml: '',
      cardFooterHtml: '',
    },
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
    const data = (props.data as typeof cards | undefined) ?? cards;
    const selection = useSelectable<number>({ids: data.map((card) => card.id)});

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(ElementCards, {data, selection, selectable: true, ...props} as any),
    });
    app.mount(container);

    return {root: container, selection};
  }

  it("labels each card's checkbox with that card's own title", async () => {
    const {root} = mount();
    await nextTick();

    const labels = [
      ...root.querySelectorAll('li craft-checkbox label[slot="label"]'),
    ].map((el) => el.textContent);

    expect(labels).toEqual(['Select Homepage', 'Select Untitled entry']);
  });
});
