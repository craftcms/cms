import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {FormControlPayload} from './types';

const stub = vi.hoisted(() => {
  const show = vi.fn(async () => {});
  const destroy = vi.fn();
  const createElementSelectorModal = vi.fn(
    async (_elementType: string, settings: Record<string, any>) => ({
      settings,
      show,
      destroy,
      on: vi.fn(),
    })
  );

  return {show, destroy, createElementSelectorModal};
});

// The control opens the selector itself now. The real factory mounts a second
// Vue app and pulls in the whole element index, so the seam it is driven
// through is what gets stubbed.
vi.mock(
  '@/modules/element-selector-modal/create-element-selector-modal',
  () => ({
    createElementSelectorModal: stub.createElementSelectorModal,
  })
);

// `<craft-element-select-input>` is now an inert wrapper — the control renders
// its own chips and drives its own modal — so an empty custom element is a
// faithful stand-in for it.
if (!customElements.get('craft-element-select-input')) {
  customElements.define(
    'craft-element-select-input',
    class extends HTMLElement {}
  );
}

// Imported after the mock so the component picks it up.
const {default: ElementSelectControl} =
  await import('./ElementSelectControl.vue');

function control(props: Record<string, unknown> = {}): FormControlPayload<any> {
  return {
    type: 'CraftCms\\Cms\\Form\\Controls\\ElementSelect',
    component: 'craft:element-select',
    props: {
      elementType: 'CraftCms\\Cms\\Elements\\Entry',
      customElement: 'craft-element-select-input',
      elements: [{id: 5, label: 'Some entry'}],
      sources: null,
      criteria: {},
      selectionLabel: 'Add an entry',
      limit: null,
      showSiteMenu: false,
      viewMode: 'list',
      ...props,
    },
    path: ['related'],
    mode: 'editable',
    deltaGroup: ['related'],
  } as FormControlPayload<any>;
}

describe('ElementSelectControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  const createElementEditor = vi.fn();

  beforeEach(() => {
    // The menu items' `<craft-icon>`s fetch their SVGs; left in flight they're
    // aborted at teardown and reported as unhandled errors.
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => new Response('<svg></svg>'))
    );
    // Double-click hands off to the global editor; only the hand-off matters here.
    vi.stubGlobal('Craft', {createElementEditor});
    createElementEditor.mockClear();
  });

  afterEach(() => {
    app?.unmount();
    container?.remove();
    vi.unstubAllGlobals();
    stub.createElementSelectorModal.mockClear();
    stub.show.mockClear();
    stub.destroy.mockClear();
  });

  let updates: Array<Array<number>>;

  async function mount(
    options: {
      props?: Record<string, unknown>;
      editable?: boolean;
      value?: number[];
    } = {}
  ): Promise<HTMLElement> {
    updates = [];
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(ElementSelectControl, {
          control: control(options.props),
          value: options.value ?? [5],
          editable: options.editable ?? true,
          'onUpdate:value': (value: number[]) => updates.push(value),
        }),
    });
    app.mount(container);
    await nextTick();

    return container;
  }

  /**
   * `openSelector` reaches the factory through a dynamic `import()`, so the
   * call lands a few microtasks after the click rather than on `nextTick`.
   */
  async function flushSelector(): Promise<void> {
    for (let i = 0; i < 20; i++) {
      if (stub.createElementSelectorModal.mock.calls.length > 0) return;
      await new Promise((resolve) => setTimeout(resolve, 0));
    }
  }

  function menus(root: HTMLElement): any[] {
    return [
      ...root.querySelectorAll('craft-chip [slot="suffix"] craft-action-menu'),
    ];
  }

  it('renders exactly one action menu per chip, with Replace and Remove', async () => {
    const root = await mount();

    expect(root.querySelectorAll('craft-chip')).toHaveLength(1);
    expect(menus(root)).toHaveLength(1);
    expect(menus(root)[0].actions.map((action: any) => action.label)).toEqual([
      'Replace',
      'Remove',
    ]);
  });

  it('removes the chip from the value', async () => {
    const root = await mount({value: [5, 6]});
    const [remove] = menus(root)[0].actions.slice(-1);

    remove.onClick();

    expect(updates).toEqual([[6]]);
  });

  it('opens the selector to replace the chip, excluding the others', async () => {
    const root = await mount({value: [5, 6]});
    const replace = menus(root)[0].actions.find(
      (action: any) => action.label === 'Replace'
    );

    replace.onClick();
    await flushSelector();

    expect(stub.createElementSelectorModal).toHaveBeenCalled();
    const [, settings] = stub.createElementSelectorModal.mock.calls[0]!;
    // The element being replaced stays selectable; its siblings do not.
    expect(settings.disabledElementIds).toEqual([6]);
    expect(settings.multiSelect).toBe(false);
  });

  it('appends what the selector returns', async () => {
    await mount({value: [5]});
    const button = container!.querySelector('craft-button')!;

    (button as HTMLElement).click();
    await flushSelector();

    const [, settings] = stub.createElementSelectorModal.mock.calls[0]!;
    settings.onSelect([{id: 9, label: 'Another entry', siteId: 1}]);

    expect(updates).toEqual([[5, 9]]);
  });

  it('drops Replace when there is no element type to pick from', async () => {
    const root = await mount({props: {elementType: null}});

    expect(menus(root)[0].actions.map((action: any) => action.label)).toEqual([
      'Remove',
    ]);
  });

  it('renders no chip actions when the control is read-only', async () => {
    const root = await mount({editable: false});

    expect(root.querySelectorAll('craft-chip')).toHaveLength(1);
    expect(menus(root)).toHaveLength(0);
  });

  it('offers the element’s own actions when the server allows them', async () => {
    const root = await mount({
      props: {
        elementDisplayName: 'entry',
        elements: [
          {
            id: 5,
            label: 'Some entry',
            url: 'https://example.test/some-entry',
            canEdit: true,
            canCopy: true,
          },
        ],
      },
    });

    expect(menus(root)[0].actions.map((a: any) => a.label)).toEqual([
      'View in a new tab',
      'Edit entry',
      'Copy entry',
      'Replace',
      'Remove',
    ]);
  });

  it('drops the element’s actions the server withheld', async () => {
    const root = await mount({
      props: {
        elements: [{id: 5, label: 'Some entry', url: null, canEdit: false}],
      },
    });

    expect(menus(root)[0].actions.map((a: any) => a.label)).toEqual([
      'Replace',
      'Remove',
    ]);
  });

  it('keeps the element’s actions on a read-only field', async () => {
    const root = await mount({
      editable: false,
      props: {
        elementDisplayName: 'entry',
        elements: [{id: 5, label: 'Some entry', canEdit: true}],
      },
    });

    expect(menus(root)[0].actions.map((a: any) => a.label)).toEqual([
      'Edit entry',
    ]);
  });
  function addButton(root: HTMLElement): HTMLElement | null {
    return root.querySelector('[data-element-select-add]');
  }

  function uploadButton(root: HTMLElement): HTMLElement | null {
    return root.querySelector('input[name="assets-upload"]');
  }

  it('keeps the add control while the selection is under the limit', async () => {
    const root = await mount({props: {limit: 3}, value: [5, 6]});

    expect(addButton(root)).not.toBeNull();
  });

  it('hides the add control once the limit is reached', async () => {
    const root = await mount({props: {limit: 3}, value: [5, 6, 7]});

    expect(addButton(root)).toBeNull();
  });

  it('hides the add control when the selection is over the limit', async () => {
    const root = await mount({props: {limit: 2}, value: [5, 6, 7]});

    expect(addButton(root)).toBeNull();
  });

  it('keeps the add control when the field has no limit', async () => {
    const root = await mount({props: {limit: null}, value: [5, 6, 7]});

    expect(addButton(root)).not.toBeNull();
  });

  // A full single-relation field is at its limit like any other; Replace on the
  // chip is how its selection changes from there.
  it('hides the add control on a full single-relation field', async () => {
    const root = await mount({props: {limit: 1}, value: [5]});

    expect(addButton(root)).toBeNull();
  });

  it('keeps the add control on an empty single-relation field', async () => {
    const root = await mount({props: {limit: 1}, value: []});

    expect(addButton(root)).not.toBeNull();
  });

  it('offers the upload control while the field has room', async () => {
    const root = await mount({
      props: {limit: 3, canUpload: true},
      value: [5, 6],
    });

    expect(uploadButton(root)).not.toBeNull();
  });

  it('hides the upload control alongside the add control at the limit', async () => {
    const root = await mount({
      props: {limit: 3, canUpload: true},
      value: [5, 6, 7],
    });

    expect(uploadButton(root)).toBeNull();
  });
  function chips(root: HTMLElement): HTMLElement[] {
    return [...root.querySelectorAll<HTMLElement>('craft-chip')];
  }

  /**
   * Stands in for `craft-chip`'s own checkbox reporting a new state. The chip
   * owns the control and the shift capture, so the field only ever sees the
   * `selected-change` it emits.
   */
  function selectChip(
    chip: HTMLElement,
    selected: boolean,
    shiftKey = false
  ): void {
    chip.dispatchEvent(
      new CustomEvent('selected-change', {detail: {selected, shiftKey}})
    );
  }

  function isSelectable(chip: HTMLElement): boolean {
    return (chip as HTMLElement & {selectable?: boolean}).selectable === true;
  }

  function selectedChips(root: HTMLElement): string[] {
    return chips(root)
      .filter((chip) => chip.hasAttribute('selected'))
      .map((chip) => chip.dataset.id!);
  }

  it('makes the chips selectable when more than one element can be related', async () => {
    const root = await mount({props: {limit: 3}, value: [5, 6]});

    expect(chips(root).every(isSelectable)).toBe(true);
  });

  it('leaves the chips unselectable on a single-relation field', async () => {
    const root = await mount({props: {limit: 1}, value: [5]});

    expect(chips(root).some(isSelectable)).toBe(false);
  });

  it('makes the chips selectable when the field has no limit', async () => {
    const root = await mount({props: {limit: null}, value: [5, 6]});

    expect(chips(root).every(isSelectable)).toBe(true);
  });

  it('leaves the chips unselectable on a read-only field', async () => {
    const root = await mount({props: {limit: 3}, value: [5], editable: false});

    expect(chips(root).some(isSelectable)).toBe(false);
  });

  it('ignores a body click on a single-relation field', async () => {
    const root = await mount({props: {limit: 1}, value: [5]});

    chips(root)[0]!.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    await nextTick();

    expect(selectedChips(root)).toEqual([]);
  });

  it('selects through the chip’s own checkbox', async () => {
    const root = await mount({props: {limit: 3}, value: [5, 6]});

    selectChip(chips(root)[0]!, true);
    await nextTick();
    expect(selectedChips(root)).toEqual(['5']);

    selectChip(chips(root)[0]!, false);
    await nextTick();
    expect(selectedChips(root)).toEqual([]);
  });

  it('selects a range when the chip reports a shift-click', async () => {
    const root = await mount({props: {limit: 3}, value: [5, 6, 7]});

    selectChip(chips(root)[0]!, true);
    await nextTick();
    selectChip(chips(root)[2]!, true, true);
    await nextTick();

    expect(selectedChips(root)).toEqual(['5', '6', '7']);
  });

  it('names the element in the chip’s select label', async () => {
    const root = await mount({props: {limit: 3}, value: [5]});

    expect(chips(root)[0]!.getAttribute('select-label')).toBe(
      'Select Some entry'
    );
  });
  describe('view modes', () => {
    function chipList(root: HTMLElement): HTMLElement | null {
      return root.querySelector('ul.element-chips');
    }

    it('draws chips in list mode', async () => {
      const root = await mount({props: {viewMode: 'list'}, value: [5]});

      expect(root.querySelectorAll('craft-chip')).toHaveLength(1);
      expect(chipList(root)?.className).not.toContain('element-chips--inline');
    });

    it('lays the chips out inline in list-inline mode', async () => {
      const root = await mount({props: {viewMode: 'list-inline'}, value: [5]});

      expect(root.querySelectorAll('craft-chip')).toHaveLength(1);
      expect(chipList(root)?.className).toContain('element-chips--inline');
    });

    it('draws cards in cards mode', async () => {
      const root = await mount({props: {viewMode: 'cards'}, value: [5]});

      expect(root.querySelectorAll('craft-card')).toHaveLength(1);
      expect(root.querySelectorAll('craft-chip')).toHaveLength(0);
    });

    it('draws cards in cards-grid mode too', async () => {
      const root = await mount({props: {viewMode: 'cards-grid'}, value: [5]});

      expect(root.querySelectorAll('craft-card')).toHaveLength(1);
    });

    it('draws thumbnails in thumbs mode', async () => {
      const root = await mount({props: {viewMode: 'thumbs'}, value: [5]});

      expect(root.querySelector('.thumb-tile')).not.toBeNull();
      expect(root.querySelectorAll('craft-chip')).toHaveLength(0);
    });

    /** The list item a card / thumb body draws per element. */
    function tiles(root: HTMLElement, selector: string): HTMLElement[] {
      return [...root.querySelectorAll<HTMLElement>(selector)];
    }

    it('selects a card by clicking it', async () => {
      const root = await mount({
        props: {viewMode: 'cards', limit: 3},
        value: [5, 6],
      });
      const items = tiles(root, '.card-grid > li');

      expect(items).toHaveLength(2);

      items[0]!.dispatchEvent(new MouseEvent('click', {bubbles: true}));
      await nextTick();

      expect(
        tiles(root, '.card-grid > li').map((li) => li.className.includes('sel'))
      ).toEqual([true, false]);
    });

    /**
     * Thumb tiles wrap the element in a link to its editor, so clicking the tile
     * navigates rather than selects — the checkbox is the selection path, as it
     * is on the element index.
     */
    it('selects a thumbnail through its checkbox', async () => {
      const root = await mount({
        props: {viewMode: 'thumbs', limit: 3},
        value: [5, 6],
      });
      const box = root.querySelector<HTMLElement>(
        '.thumbsview craft-checkbox'
      )!;

      Object.defineProperty(box, 'checked', {value: true, configurable: true});
      box.dispatchEvent(
        new CustomEvent('model-value-changed', {bubbles: true})
      );
      await nextTick();

      expect(
        tiles(root, '.thumbsview > li').map((li) =>
          li.className.includes('sel')
        )
      ).toEqual([true, false]);
    });

    it('selects a card through its checkbox too', async () => {
      const root = await mount({
        props: {viewMode: 'cards', limit: 3},
        value: [5, 6],
      });
      const box = root.querySelector<HTMLElement>('.card-grid craft-checkbox')!;

      Object.defineProperty(box, 'checked', {value: true, configurable: true});
      box.dispatchEvent(
        new CustomEvent('model-value-changed', {bubbles: true})
      );
      await nextTick();

      expect(
        tiles(root, '.card-grid > li').map((li) => li.className.includes('sel'))
      ).toEqual([true, false]);
    });

    // The bodies render from `value`, not the server's element list, so a
    // selection can't target an element that isn't on screen.
    it('draws a card per related element, not per server-rendered element', async () => {
      const root = await mount({props: {viewMode: 'cards'}, value: [5, 6, 7]});

      expect(root.querySelectorAll('.card-grid > li')).toHaveLength(3);
    });

    // The bodies take a `selection`, so the field's own gate reaches them.
    it('carries the selectable gate into a card body', async () => {
      const root = await mount({
        props: {viewMode: 'cards', limit: 1},
        value: [5],
      });

      expect(root.querySelector('.card-grid-header')).toBeNull();
    });
  });
  describe('double-click to edit', () => {
    function chip(root: HTMLElement): HTMLElement {
      return root.querySelector('craft-chip')!;
    }

    /**
     * A dblclick whose composed path starts at `origin`, as a real one would.
     *
     * Non-bubbling on purpose: happy-dom invokes a listener on the event's own
     * target in both the at-target and bubbling phases, so a bubbling dispatch
     * would double-count a handler a real browser fires once.
     */
    function doubleClick(target: HTMLElement, origin: Element = target): void {
      const event = new MouseEvent('dblclick', {bubbles: false});
      Object.defineProperty(event, 'composedPath', {
        value: () => [origin, target],
      });
      target.dispatchEvent(event);
    }

    it('opens the editor when the chip body is double-clicked', async () => {
      const root = await mount({value: [5]});

      doubleClick(chip(root));

      expect(createElementEditor).toHaveBeenCalledTimes(1);
      expect(createElementEditor).toHaveBeenCalledWith(
        'CraftCms\\Cms\\Elements\\Entry',
        expect.objectContaining({elementId: 5})
      );
    });

    it('stays put when the double-click lands on the chip’s checkbox', async () => {
      const root = await mount({props: {limit: 3}, value: [5]});
      // `craft-chip` renders its own checkbox in shadow DOM; the composed path
      // is what surfaces it.
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';

      doubleClick(chip(root), checkbox);

      expect(createElementEditor).not.toHaveBeenCalled();
    });

    it('stays put when the double-click lands on a button in the chip', async () => {
      const root = await mount({value: [5]});
      const button = document.createElement('button');

      doubleClick(chip(root), button);

      expect(createElementEditor).not.toHaveBeenCalled();
    });
  });
  describe('selection toolbar', () => {
    function selectAllBox(root: HTMLElement): HTMLElement | null {
      return root.querySelector('craft-checkbox');
    }

    /** Scoped to the toolbar: the dev view-mode toggle also carries `.text-xs`. */
    function countText(root: HTMLElement): string {
      const bar = selectAllBox(root)?.parentElement;

      return bar?.querySelector('.text-xs')?.textContent?.trim() ?? '';
    }

    function clearButton(root: HTMLElement): HTMLElement | undefined {
      return [...root.querySelectorAll<HTMLElement>('craft-button')].find(
        (button) => button.textContent?.includes('Clear selection')
      );
    }

    /** The toolbar's own menu — each chip carries one too. */
    function actionsMenu(root: HTMLElement): HTMLElement | null {
      const bar = selectAllBox(root)?.parentElement?.parentElement;

      return bar?.querySelector(':scope > craft-action-menu') ?? null;
    }

    /** Reports a new checked state the way `craft-checkbox` does. */
    function check(box: HTMLElement, checked: boolean): void {
      Object.defineProperty(box, 'checked', {
        value: checked,
        configurable: true,
      });
      box.dispatchEvent(
        new CustomEvent('model-value-changed', {bubbles: true})
      );
    }

    function selectFirstChip(root: HTMLElement): void {
      root.querySelector('craft-chip')!.dispatchEvent(
        new CustomEvent('selected-change', {
          detail: {selected: true, shiftKey: false},
        })
      );
    }

    it('has no toolbar on a field that can’t be selected', async () => {
      const root = await mount({props: {limit: 1}, value: [5]});

      expect(selectAllBox(root)).toBeNull();
    });

    it('shows no count, clear button or actions until something is selected', async () => {
      const root = await mount({props: {limit: 3}, value: [5, 6]});

      expect(selectAllBox(root)).not.toBeNull();
      expect(countText(root)).toBe('');
      expect(clearButton(root)).toBeUndefined();
      expect(actionsMenu(root)).toBeNull();
    });

    it('counts the selection once something is selected', async () => {
      const root = await mount({props: {limit: 3}, value: [5, 6]});

      selectFirstChip(root);
      await nextTick();

      expect(countText(root)).toContain('1');
      expect(clearButton(root)).toBeDefined();
      expect(actionsMenu(root)).not.toBeNull();
    });

    it('counts every selected item', async () => {
      const root = await mount({props: {limit: 3}, value: [5, 6, 7]});

      check(selectAllBox(root)!, true);
      await nextTick();

      expect(countText(root)).toContain('3');
    });

    it('selects everything from the toolbar checkbox', async () => {
      const root = await mount({props: {limit: 3}, value: [5, 6, 7]});

      check(selectAllBox(root)!, true);
      await nextTick();

      expect(
        [...root.querySelectorAll('craft-chip')].map((chip) =>
          chip.hasAttribute('selected')
        )
      ).toEqual([true, true, true]);
    });

    it('deselects everything when the toolbar checkbox is cleared', async () => {
      const root = await mount({props: {limit: 3}, value: [5, 6]});

      check(selectAllBox(root)!, true);
      await nextTick();
      check(selectAllBox(root)!, false);
      await nextTick();

      expect(
        [...root.querySelectorAll('craft-chip')].some((chip) =>
          chip.hasAttribute('selected')
        )
      ).toBe(false);
    });

    it('clears the selection from the clear button', async () => {
      const root = await mount({props: {limit: 3}, value: [5, 6]});

      selectFirstChip(root);
      await nextTick();
      clearButton(root)!.dispatchEvent(
        new MouseEvent('click', {bubbles: true})
      );
      await nextTick();

      expect(countText(root)).toBe('');
      expect(clearButton(root)).toBeUndefined();
      expect(
        [...root.querySelectorAll('craft-chip')].some((chip) =>
          chip.hasAttribute('selected')
        )
      ).toBe(false);
    });

    it('reflects a partial selection as indeterminate, and a full one as checked', async () => {
      const root = await mount({props: {limit: 3}, value: [5, 6]});
      const box = selectAllBox(root)! as HTMLElement & {
        checked?: boolean;
        indeterminate?: boolean;
      };

      selectFirstChip(root);
      await nextTick();
      expect({checked: box.checked, indeterminate: box.indeterminate}).toEqual({
        checked: false,
        indeterminate: true,
      });

      check(box, true);
      await nextTick();
      expect({checked: box.checked, indeterminate: box.indeterminate}).toEqual({
        checked: true,
        indeterminate: false,
      });
    });
  });
  describe('bulk actions', () => {
    const copyable = [
      {id: 5, label: 'One', canCopy: true, siteId: 1},
      {id: 6, label: 'Two', canCopy: true, siteId: 1},
      {id: 7, label: 'Three', canCopy: false},
    ];

    function selectAllBox(root: HTMLElement): HTMLElement | null {
      return root.querySelector('craft-checkbox');
    }

    function bulkMenu(root: HTMLElement): any {
      const bar = selectAllBox(root)?.parentElement?.parentElement;

      return bar?.querySelector(':scope > craft-action-menu') ?? null;
    }

    function labels(root: HTMLElement): string[] {
      return (bulkMenu(root)?.actions ?? []).map((action: any) => action.label);
    }

    async function mountSelected(props: Record<string, unknown> = {}) {
      const root = await mount({
        props: {limit: 5, elements: copyable, ...props},
        value: [5, 6, 7],
      });

      const box = selectAllBox(root)!;
      Object.defineProperty(box, 'checked', {value: true, configurable: true});
      box.dispatchEvent(
        new CustomEvent('model-value-changed', {bubbles: true})
      );
      await nextTick();

      return root;
    }

    it('offers Copy and Remove for a selection', async () => {
      const root = await mountSelected();

      expect(labels(root)).toEqual(['Copy selected', 'Remove selected']);
    });

    it('removes every selected element', async () => {
      const root = await mountSelected();

      labels(root); // menu is built
      bulkMenu(root)
        .actions.find((a: any) => a.label === 'Remove selected')
        .onClick();
      await nextTick();

      expect(updates.at(-1)).toEqual([]);
    });

    it('removes only the selection, leaving the rest related', async () => {
      const root = await mount({
        props: {limit: 5, elements: copyable},
        value: [5, 6, 7],
      });

      root.querySelector('craft-chip')!.dispatchEvent(
        new CustomEvent('selected-change', {
          detail: {selected: true, shiftKey: false},
        })
      );
      await nextTick();

      bulkMenu(root)
        .actions.find((a: any) => a.label === 'Remove selected')
        .onClick();
      await nextTick();

      expect(updates.at(-1)).toEqual([6, 7]);
    });

    it('copies the selected elements through the clipboard', async () => {
      const copyElements = vi.fn();
      vi.stubGlobal('Craft', {
        createElementEditor: vi.fn(),
        cp: {copyElements},
      });

      const root = await mountSelected();
      bulkMenu(root)
        .actions.find((a: any) => a.label === 'Copy selected')
        .onClick();

      // Only the two the server said may be copied.
      expect(copyElements).toHaveBeenCalledTimes(1);
      expect(copyElements.mock.calls[0]![0].map((d: any) => d.id)).toEqual([
        5, 6,
      ]);
    });

    it('drops Copy when nothing selected can be copied', async () => {
      const root = await mountSelected({
        elements: [
          {id: 5, label: 'One'},
          {id: 6, label: 'Two'},
          {id: 7, label: 'Three'},
        ],
      });

      expect(labels(root)).toEqual(['Remove selected']);
    });

    /**
     * Selection needs an editable field, so a read-only one has no toolbar and
     * therefore no bulk menu — the per-chip actions are still how you copy from
     * one. `bulkActions` guards Remove on `editable` regardless, so the list
     * stays correct if the toolbar's own gate ever changes.
     */
    it('offers no bulk menu on a read-only field', async () => {
      const root = await mount({
        props: {limit: 5, elements: copyable},
        value: [5, 6, 7],
        editable: false,
      });

      expect(bulkMenu(root)).toBeNull();
    });
  });
});
