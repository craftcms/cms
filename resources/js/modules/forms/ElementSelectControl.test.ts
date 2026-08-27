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

/** Menu labels, with separators spelled out so the grouping is visible. */
function actionLabels(actions: Array<any>): string[] {
  return actions.map((action) => (action.type === 'hr' ? '---' : action.label));
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
    expect(actionLabels(menus(root)[0].actions)).toEqual([
      'Replace',
      '---',
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

    expect(actionLabels(menus(root)[0].actions)).toEqual(['Remove']);
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

    expect(actionLabels(menus(root)[0].actions)).toEqual([
      // The element's own actions, then the field's, then detaching it.
      'View in a new tab',
      'Copy entry',
      '---',
      'Edit entry',
      'Replace',
      '---',
      'Remove',
    ]);
  });

  it('drops the element’s actions the server withheld', async () => {
    const root = await mount({
      props: {
        elements: [{id: 5, label: 'Some entry', url: null, canEdit: false}],
      },
    });

    expect(actionLabels(menus(root)[0].actions)).toEqual([
      'Replace',
      '---',
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

    expect(actionLabels(menus(root)[0].actions)).toEqual(['Edit entry']);
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
    /** Select-all checkboxes: the toolbar's, plus any a body draws of its own. */
    function selectAllBoxes(root: HTMLElement): HTMLElement[] {
      return [...root.querySelectorAll<HTMLElement>('craft-checkbox')].filter(
        (box) => box.closest('.card-grid > li, .thumbsview > li') === null
      );
    }

    // The toolbar above the list owns select-all; the bodies would otherwise
    // draw a second one.
    it('draws only one select-all in a card or thumb mode', async () => {
      for (const viewMode of ['cards', 'cards-grid', 'thumbs'] as const) {
        const root = await mount({props: {viewMode, limit: 3}, value: [5, 6]});

        // The bodies' own select-all headers are gone; the per-element
        // checkboxes inside each card or tile stay.
        expect(root.querySelector('.card-grid-header')).toBeNull();
        expect(root.querySelector('.thumbsview-header')).toBeNull();
        expect(selectAllBoxes(root)).toHaveLength(1);

        app?.unmount();
        container?.remove();
      }
    });

    // The thumb belongs in the card's own `thumbnail` slot, not inline in the
    // content, so the card can lay it out and align it.
    it('puts a card thumbnail in the card’s thumbnail slot', async () => {
      const root = await mount({
        props: {
          viewMode: 'cards',
          elements: [
            {
              id: 5,
              label: 'One',
              cardThumbHtml: '<img class="a-thumb" />',
              thumbAlignment: 'start',
            },
          ],
        },
        value: [5],
      });
      const card = root.querySelector('craft-card')!;

      expect(card.querySelector('[slot="thumbnail"] .a-thumb')).not.toBeNull();
      expect(card.getAttribute('thumb-alignment')).toBe('start');
    });

    it('draws no thumbnail region when the server baked it into the content', async () => {
      const root = await mount({
        props: {viewMode: 'cards', elements: [{id: 5, label: 'One'}]},
        value: [5],
      });

      expect(root.querySelector('craft-card [slot="thumbnail"]')).toBeNull();
    });

    it('stacks cards one per row in cards mode', async () => {
      const root = await mount({props: {viewMode: 'cards'}, value: [5, 6]});

      expect(root.querySelector('ul.card-grid')!.className).toContain(
        'card-grid--single'
      );
    });

    it('lays cards out in a grid in cards-grid mode', async () => {
      const root = await mount({
        props: {viewMode: 'cards-grid'},
        value: [5, 6],
      });

      expect(root.querySelector('ul.card-grid')!.className).not.toContain(
        'card-grid--single'
      );
    });

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
      return actionLabels(bulkMenu(root)?.actions ?? []);
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
  describe('element actions', () => {
    function menuLabels(root: HTMLElement): string[] {
      const menu = root.querySelector(
        'craft-chip [slot="suffix"] craft-action-menu'
      ) as any;

      return actionLabels(menu?.actions ?? []);
    }

    /**
     * An element type's extras — an asset's Preview file, Download, Show in
     * folder, Open in Image Editor — are described by the server, so the field
     * renders whatever it's given rather than a fixed list.
     */
    it('renders the action menu the server described', async () => {
      const root = await mount({
        props: {
          elements: [
            {
              id: 5,
              label: 'photo.jpg',
              actions: [
                {
                  label: 'Preview file',
                  icon: 'view',
                  behavior: {type: 'previewFile', assetId: 5},
                },
                {
                  label: 'Download',
                  icon: 'download',
                  behavior: {
                    type: 'download',
                    actionUrl: '/download',
                    params: {assetId: 5},
                  },
                },
                {
                  label: 'Show in folder',
                  icon: 'magnifying-glass',
                  behavior: {type: 'link', href: '/show-in-folder'},
                },
                {
                  label: 'Open in Image Editor',
                  icon: 'edit',
                  behavior: {type: 'editImage', assetId: 5},
                },
              ],
            },
          ],
        },
        value: [5],
      });

      expect(menuLabels(root)).toEqual([
        'Preview file',
        'Download',
        'Show in folder',
        'Open in Image Editor',
        '---',
        'Replace',
        '---',
        'Remove',
      ]);
    });

    // The server can put a rule between its own groups; it survives the mapping
    // into menu items rather than turning into a broken button.
    it('renders a separator the server described', async () => {
      const root = await mount({
        props: {
          elements: [
            {
              id: 5,
              label: 'photo.jpg',
              actions: [
                {
                  label: 'Download',
                  behavior: {type: 'download', actionUrl: '/d', params: {}},
                },
                {type: 'hr'},
                {
                  label: 'Open in Image Editor',
                  behavior: {type: 'editImage', assetId: 5},
                },
              ],
            },
          ],
        },
        value: [5],
      });

      expect(menuLabels(root).slice(0, 3)).toEqual([
        'Download',
        '---',
        'Open in Image Editor',
      ]);
    });

    it('dispatches a described action when its item is clicked', async () => {
      const root = await mount({
        props: {
          elements: [
            {
              id: 5,
              label: 'photo.jpg',
              actions: [
                {
                  label: 'Show in folder',
                  behavior: {type: 'link', href: '/show-in-folder'},
                },
              ],
            },
          ],
        },
        value: [5],
      });
      const open = vi.fn();
      vi.stubGlobal('open', open);

      const menu = root.querySelector(
        'craft-chip [slot="suffix"] craft-action-menu'
      ) as any;
      menu.actions
        .find((action: any) => action.label === 'Show in folder')
        .onClick();

      expect(open).toHaveBeenCalledWith('/show-in-folder', '_self', undefined);
    });

    // The selector modal reports only a label, so a freshly picked element has
    // no descriptors until the next server render.
    it('falls back to client-derived actions for an element with none described', async () => {
      const root = await mount({
        props: {
          elementDisplayName: 'entry',
          elements: [
            {
              id: 5,
              label: 'Some entry',
              url: 'https://example.test',
              canEdit: true,
            },
          ],
        },
        value: [5],
      });

      expect(menuLabels(root)).toEqual([
        'View in a new tab',
        '---',
        'Edit entry',
        'Replace',
        '---',
        'Remove',
      ]);
    });
  });
  describe('card actions', () => {
    const asset = {
      id: 5,
      label: 'photo.jpg',
      canEdit: true,
      actions: [
        {
          label: 'Preview file',
          icon: 'view',
          behavior: {type: 'previewFile', assetId: 5},
        },
      ],
    };

    /**
     * Scoped to the header slot on purpose. `craft-card`'s `actions` slot is
     * fallback content inside its `header` slot, so anything supplied as
     * `slot="actions"` alongside a supplied header is never slotted and never
     * renders — while still matching a plain light-DOM query.
     */
    function cardMenu(root: HTMLElement): any {
      return root.querySelector('craft-card [slot="header"] craft-action-menu');
    }

    /** Selected by its label: `icon` is a property on craft-button, not an attribute. */
    function pencil(root: HTMLElement): HTMLElement | null {
      return root.querySelector(
        'craft-card [slot="header"] craft-button[aria-label="Edit asset"]'
      );
    }

    it('gives a card its action menu', async () => {
      const root = await mount({
        props: {
          viewMode: 'cards',
          elementDisplayName: 'asset',
          elements: [asset],
        },
        value: [5],
      });

      expect(cardMenu(root)).not.toBeNull();
      expect(actionLabels(cardMenu(root).actions)).toContain('Preview file');
    });

    // The pencil covers editing on a card, so the menu doesn't repeat it.
    it('gives a card a pencil to the editor and drops Edit from its menu', async () => {
      const root = await mount({
        props: {
          viewMode: 'cards',
          elementDisplayName: 'asset',
          elements: [asset],
        },
        value: [5],
      });

      expect(pencil(root)).not.toBeNull();
      expect(pencil(root)!.closest('craft-card')).not.toBeNull();
      expect(actionLabels(cardMenu(root).actions)).not.toContain('Edit asset');
    });

    it('opens the editor from the card pencil', async () => {
      const root = await mount({
        props: {
          viewMode: 'cards',
          elementDisplayName: 'asset',
          elements: [asset],
        },
        value: [5],
      });

      pencil(root)!.dispatchEvent(new MouseEvent('click', {bubbles: true}));

      expect(createElementEditor).toHaveBeenCalledWith(
        'CraftCms\\Cms\\Elements\\Entry',
        expect.objectContaining({elementId: 5})
      );
    });

    it('offers no pencil for an element the user can’t edit', async () => {
      const root = await mount({
        props: {
          viewMode: 'cards',
          elementDisplayName: 'asset',
          elements: [{...asset, canEdit: false}],
        },
        value: [5],
      });

      expect(pencil(root)).toBeNull();
    });

    // A chip keeps editing in its menu rather than as a separate control.
    it('keeps Edit in a chip’s menu, with no pencil', async () => {
      const root = await mount({
        props: {
          viewMode: 'list',
          elementDisplayName: 'asset',
          elements: [asset],
        },
        value: [5],
      });
      const menu = root.querySelector(
        'craft-chip [slot="suffix"] craft-action-menu'
      ) as any;

      expect(pencil(root)).toBeNull();
      expect(actionLabels(menu.actions)).toContain('Edit asset');
    });
  });
  describe('thumb actions', () => {
    const asset = {
      id: 5,
      label: 'photo.jpg',
      canEdit: true,
      actions: [
        {
          label: 'Preview file',
          icon: 'view',
          behavior: {type: 'previewFile', assetId: 5},
        },
      ],
    };

    async function mountThumbs() {
      return mount({
        props: {
          viewMode: 'thumbs',
          elementDisplayName: 'asset',
          elements: [asset],
        },
        value: [5],
      });
    }

    function thumbMenu(root: HTMLElement): any {
      return root.querySelector('.thumbsview .thumb-actions craft-action-menu');
    }

    it('gives a thumbnail its action menu', async () => {
      const root = await mountThumbs();

      expect(thumbMenu(root)).not.toBeNull();
      expect(actionLabels(thumbMenu(root).actions)).toContain('Preview file');
    });

    // Tiles are a link to the element, so the actions have to sit outside it or
    // reaching for one would navigate instead.
    it('keeps the actions outside the tile link', async () => {
      const root = await mountThumbs();

      expect(thumbMenu(root)!.closest('.thumb-tile')).toBeNull();
    });

    // Thumbs follow the chips: editing lives in the menu, not as its own button.
    it('keeps Edit in the thumbnail menu, with no pencil', async () => {
      const root = await mountThumbs();

      expect(
        root.querySelector('craft-button[aria-label="Edit asset"]')
      ).toBeNull();
      expect(actionLabels(thumbMenu(root).actions)).toContain('Edit asset');
    });
  });
  describe('menu grouping', () => {
    it('never opens or closes on a separator', async () => {
      const root = await mount({props: {elements: [{id: 5, label: 'One'}]}});
      const labels = actionLabels(menus(root)[0].actions);

      expect(labels.at(0)).not.toBe('---');
      expect(labels.at(-1)).not.toBe('---');
    });

    // A read-only field has no Replace or Remove, so the sections either side
    // of them collapse rather than leaving a stray rule.
    it('drops the separator when a section is empty', async () => {
      const root = await mount({
        props: {
          elementDisplayName: 'entry',
          elements: [{id: 5, label: 'One', canEdit: true}],
        },
        editable: false,
      });

      expect(actionLabels(menus(root)[0].actions)).toEqual(['Edit entry']);
    });
  });
  describe('reordering', () => {
    function handles(root: HTMLElement): HTMLElement[] {
      return [...root.querySelectorAll<HTMLElement>('craft-reorder-button')];
    }

    function orientations(root: HTMLElement): (string | null)[] {
      return handles(root).map((handle) => handle.getAttribute('orientation'));
    }

    it('gives every card a reorder handle when the field is sortable', async () => {
      const root = await mount({props: {viewMode: 'cards'}, value: [5, 6]});

      expect(handles(root)).toHaveLength(2);
    });

    it('offers no handles on a field that can’t be sorted', async () => {
      // One related element: nothing to reorder.
      const root = await mount({props: {viewMode: 'cards'}, value: [5]});

      expect(handles(root)).toHaveLength(0);
    });

    it('reorders the value from a card handle', async () => {
      const root = await mount({props: {viewMode: 'cards'}, value: [5, 6, 7]});

      handles(root)[1]!.dispatchEvent(
        new CustomEvent('reorder', {detail: {direction: 'up'}})
      );
      await nextTick();

      expect(updates.at(-1)).toEqual([6, 5, 7]);
    });

    /**
     * A wrapping layout runs in reading order, so its moves read as
     * forward/backward; a single column reads as up/down.
     */
    it('moves forward and backward in a card grid', async () => {
      const root = await mount({
        props: {viewMode: 'cards-grid'},
        value: [5, 6],
      });

      expect(orientations(root)).toEqual(['horizontal', 'horizontal']);
    });

    it('moves up and down in a single-column card list', async () => {
      const root = await mount({props: {viewMode: 'cards'}, value: [5, 6]});

      expect(orientations(root)).toEqual(['vertical', 'vertical']);
    });

    it('gives every thumbnail a reorder handle', async () => {
      const root = await mount({props: {viewMode: 'thumbs'}, value: [5, 6]});

      expect(
        root.querySelectorAll('.thumbsview craft-reorder-button')
      ).toHaveLength(2);
    });

    it('reorders the value from a thumbnail handle', async () => {
      const root = await mount({props: {viewMode: 'thumbs'}, value: [5, 6, 7]});

      root
        .querySelectorAll('.thumbsview craft-reorder-button')[1]!
        .dispatchEvent(new CustomEvent('reorder', {detail: {direction: 'up'}}));
      await nextTick();

      expect(updates.at(-1)).toEqual([6, 5, 7]);
    });

    // A thumbnail grid wraps, so it reads in reading order like a card grid.
    it('moves forward and backward in a thumbnail grid', async () => {
      const root = await mount({props: {viewMode: 'thumbs'}, value: [5, 6]});

      expect(orientations(root)).toEqual(['horizontal', 'horizontal']);
    });

    it('moves forward and backward in an inline list', async () => {
      const root = await mount({
        props: {viewMode: 'list-inline'},
        value: [5, 6],
      });

      expect(orientations(root)).toEqual(['horizontal', 'horizontal']);
    });

    it('moves up and down in a stacked list', async () => {
      const root = await mount({props: {viewMode: 'list'}, value: [5, 6]});

      expect(orientations(root)).toEqual(['vertical', 'vertical']);
    });
  });
});
