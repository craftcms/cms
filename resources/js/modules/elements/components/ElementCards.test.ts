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
      cardActionsHtml: '<button data-server-action>Server action</button>',
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

  it('renders server-provided card actions when the host does not replace them', async () => {
    const {root} = mount();
    await nextTick();

    expect(root.querySelector('[data-server-action]')?.textContent).toBe(
      'Server action'
    );
  });

  // A relational field opens the element's slideout from this.
  it('asks to edit a double-clicked card, but not from its controls', async () => {
    const onEdit = vi.fn();
    const {root} = mount({onEdit});
    await nextTick();

    const first = root.querySelector('.card-grid > *')!;
    // Custom elements aren't defined here, so a plain button stands in for the
    // checkbox and the other controls a double-click should leave alone.
    const control = document.createElement('button');
    first.append(control);
    control.dispatchEvent(new MouseEvent('dblclick', {bubbles: true}));
    expect(onEdit).not.toHaveBeenCalled();

    first.dispatchEvent(new MouseEvent('dblclick', {bubbles: true}));
    expect(onEdit).toHaveBeenCalledWith(expect.objectContaining({id: 5}));
  });

  // An asset folder opens on double-click rather than being edited.
  it('leaves a double-click its item behavior handled alone', async () => {
    const onEdit = vi.fn();
    const onDblClick = vi.fn(() => true);
    const {root} = mount({onEdit, itemBehavior: {onDblClick}});
    await nextTick();

    const event = new MouseEvent('dblclick', {bubbles: true, cancelable: true});
    root.querySelector('.card-grid > *')!.dispatchEvent(event);

    expect(onDblClick).toHaveBeenCalledWith(
      expect.objectContaining({id: 5}),
      event
    );
    expect(event.defaultPrevented).toBe(true);
    expect(onEdit).not.toHaveBeenCalled();
  });
});
