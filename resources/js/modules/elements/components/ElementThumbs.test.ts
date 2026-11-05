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

    const labels = [...root.querySelectorAll('li craft-chip')].map((el) =>
      el.getAttribute('select-label')
    );

    expect(labels).toEqual(['Select Homepage', 'Select Untitled entry']);
  });

  /** Selectable tiles get the default cursor rather than a text one. */
  it.each([true, false])(
    'marks a tile selectable only while it is (%s)',
    async (selectable) => {
      const {root} = mount({selectable});
      await nextTick();

      for (const tile of root.querySelectorAll('li.element')) {
        expect(tile.classList.contains('element--selectable')).toBe(selectable);
      }
    }
  );

  it('links just the label, whether to the element or a label URL', async () => {
    const {root} = mount({
      data: [
        {
          id: 'folder:3',
          label: 'Photos',
          url: null,
          labelUrl: '/cp/assets/images/photos',
          labelLinkAttributes: {'data-folder-link': ''},
        },
        {id: 6, label: 'Report', url: '/cp/assets/edit/6'},
      ],
    });
    await nextTick();

    const [folder, asset] = root.querySelectorAll('li');
    const folderLink = folder!.querySelector('.thumb-label a');
    const assetLink = asset!.querySelector('.thumb-label a');

    expect(folderLink?.getAttribute('href')).toBe('/cp/assets/images/photos');
    expect(folderLink?.hasAttribute('data-folder-link')).toBe(true);
    expect(folderLink?.textContent).toBe('Photos');
    expect(assetLink?.getAttribute('href')).toBe('/cp/assets/edit/6');
    expect(assetLink?.textContent).toBe('Report');
    // The tile around the label isn't a link.
    expect(
      root.querySelector('a craft-chip, craft-chip a craft-chip')
    ).toBeNull();
  });

  // Only the label navigates; the rest of the tile selects, as its checkbox
  // would.
  it('selects a tile clicked outside its link', async () => {
    const {root, selection} = mount({
      data: [{id: 6, label: 'Report', url: '/cp/assets/edit/6'}],
    });
    await nextTick();

    root
      .querySelector<HTMLElement>('[slot="thumbnail"]')!
      .dispatchEvent(new MouseEvent('click', {bubbles: true}));
    await nextTick();

    expect(selection.isSelected(6)).toBe(true);
  });

  it('leaves a click on the label’s link to the link', async () => {
    const {root, selection} = mount({
      data: [{id: 6, label: 'Report', url: '/cp/assets/edit/6'}],
    });
    await nextTick();

    const link = root.querySelector<HTMLAnchorElement>('.thumb-label a')!;
    link.addEventListener('click', (event) => event.preventDefault());
    link.dispatchEvent(
      new MouseEvent('click', {bubbles: true, cancelable: true})
    );
    await nextTick();

    expect(selection.isSelected(6)).toBe(false);
  });

  it('selects a focused tile with Space, even when it links somewhere', async () => {
    const {root, selection} = mount({
      data: [{id: 6, label: 'Report', url: '/cp/assets/edit/6'}],
    });
    await nextTick();

    root
      .querySelector('li')!
      .dispatchEvent(new KeyboardEvent('keydown', {key: ' ', bubbles: true}));
    await nextTick();

    expect(selection.isSelected(6)).toBe(true);
  });

  // A relational field opens the element's slideout from this.
  it('asks to edit a double-clicked tile, but not from its controls', async () => {
    const onEdit = vi.fn();
    const {root} = mount({onEdit});
    await nextTick();

    const [first] = root.querySelectorAll('li');
    // Custom elements aren't defined here, so a plain button stands in for the
    // checkbox and the other controls a double-click should leave alone.
    const control = document.createElement('button');
    first!.append(control);
    control.dispatchEvent(new MouseEvent('dblclick', {bubbles: true}));
    expect(onEdit).not.toHaveBeenCalled();

    first!.dispatchEvent(new MouseEvent('dblclick', {bubbles: true}));
    expect(onEdit).toHaveBeenCalledWith(expect.objectContaining({id: 5}));
  });

  // An asset folder opens on double-click rather than being edited.
  it('leaves a double-click its item behavior handled alone', async () => {
    const onEdit = vi.fn();
    const onDblClick = vi.fn(() => true);
    const {root} = mount({onEdit, itemBehavior: {onDblClick}});
    await nextTick();

    const event = new MouseEvent('dblclick', {bubbles: true, cancelable: true});
    root.querySelector('li')!.dispatchEvent(event);

    expect(onDblClick).toHaveBeenCalledWith(
      expect.objectContaining({id: 5}),
      event
    );
    expect(event.defaultPrevented).toBe(true);
    expect(onEdit).not.toHaveBeenCalled();
  });
});
