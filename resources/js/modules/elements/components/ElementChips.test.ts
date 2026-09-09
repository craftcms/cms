import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import ElementChips from './ElementChips.vue';
import {useSelectable} from '@/common/composables/useSelectable';

describe('ElementChips', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  const elements = [
    {id: 5, label: 'Homepage'},
    {id: 6, label: 'About us'},
    {id: 7, label: 'Contact'},
  ];

  beforeEach(() => {
    // The chips' `<craft-icon>`s fetch their SVGs; left in flight they're
    // aborted at teardown and reported as unhandled errors.
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

  function mount(
    props: Record<string, unknown> = {},
    slots: Record<string, unknown> = {}
  ) {
    const selection = useSelectable<number>({
      ids:
        (props.data as typeof elements | undefined)?.map((e) => e.id) ??
        elements.map((e) => e.id),
      click: 'replace',
    });
    const emitted: Record<string, unknown[][]> = {edit: [], reorder: []};

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(
          ElementChips,
          {
            data: elements,
            selection,
            selectable: true,
            onEdit: (...args: unknown[]) => emitted.edit!.push(args),
            onReorder: (...args: unknown[]) => emitted.reorder!.push(args),
            ...props,
          },
          slots
        ),
    });
    app.mount(container);

    return {root: container, selection, emitted};
  }

  function chips(root: HTMLElement): HTMLElement[] {
    return [...root.querySelectorAll<HTMLElement>('craft-chip')];
  }

  /** A click whose composed path starts at `origin`, as a real one would. */
  function clickEvent(type: string, origin?: Element): MouseEvent {
    const event = new MouseEvent(type, {bubbles: false});

    if (origin) {
      Object.defineProperty(event, 'composedPath', {value: () => [origin]});
    }

    return event;
  }

  it('draws a chip per element, labelled', async () => {
    const {root} = mount();

    expect(chips(root)).toHaveLength(3);
    expect(chips(root)[0]!.textContent).toContain('Homepage');
  });

  it('falls back to the id when an element has no label', async () => {
    const {root} = mount({data: [{id: 9}]});

    expect(chips(root)[0]!.textContent).toContain('9');
  });

  it('stacks by default and wraps when inline', async () => {
    const {root} = mount();
    expect(root.querySelector('ul')!.className).not.toContain('--inline');

    app?.unmount();
    container?.remove();

    const inline = mount({inline: true});
    expect(inline.root.querySelector('ul')!.className).toContain('--inline');
  });

  describe('selection', () => {
    it('marks the chips selectable and reflects the selection', async () => {
      const {root, selection} = mount();

      expect(
        chips(root).every(
          (chip) => (chip as HTMLElement & {selectable?: boolean}).selectable
        )
      ).toBe(true);

      selection.select(6, true);
      await nextTick();

      expect(chips(root).map((chip) => chip.hasAttribute('selected'))).toEqual([
        false,
        true,
        false,
      ]);
    });

    it('selects on a click on the chip body', async () => {
      const {root, selection} = mount();

      chips(root)[0]!.dispatchEvent(clickEvent('click'));

      expect([...selection.selectedIds.value]).toEqual([5]);
    });

    it('selects through the chip’s own checkbox, shift included', async () => {
      const {root, selection} = mount();

      chips(root)[0]!.dispatchEvent(
        new CustomEvent('selected-change', {
          detail: {selected: true, shiftKey: false},
        })
      );
      chips(root)[2]!.dispatchEvent(
        new CustomEvent('selected-change', {
          detail: {selected: true, shiftKey: true},
        })
      );

      expect([...selection.selectedIds.value].sort((a, b) => a - b)).toEqual([
        5, 6, 7,
      ]);
    });

    it('leaves the chips unselectable when told not to be', async () => {
      const {root, selection} = mount({selectable: false});

      chips(root)[0]!.dispatchEvent(clickEvent('click'));

      expect(
        chips(root).some(
          (chip) => (chip as HTMLElement & {selectable?: boolean}).selectable
        )
      ).toBe(false);
      expect([...selection.selectedIds.value]).toEqual([]);
    });

    // `readOnly` has to reach the click and checkbox paths, not just the
    // checkbox's own disabled state.
    it('refuses selection on a read-only list', async () => {
      const {root, selection} = mount({readOnly: true});

      chips(root)[0]!.dispatchEvent(clickEvent('click'));
      chips(root)[1]!.dispatchEvent(
        new CustomEvent('selected-change', {
          detail: {selected: true, shiftKey: false},
        })
      );

      expect([...selection.selectedIds.value]).toEqual([]);
    });
  });

  describe('double-click to edit', () => {
    it('emits edit when the chip body is double-clicked', async () => {
      const {root, emitted} = mount();

      chips(root)[1]!.dispatchEvent(clickEvent('dblclick'));

      expect(emitted.edit).toHaveLength(1);
      expect((emitted.edit![0]![0] as {id: number}).id).toBe(6);
    });

    it('stays put when the double-click lands on a control', async () => {
      const {root, emitted} = mount();
      const button = document.createElement('button');

      chips(root)[1]!.dispatchEvent(clickEvent('dblclick', button));

      expect(emitted.edit).toHaveLength(0);
    });
  });

  describe('reordering', () => {
    it('offers a reorder button only when sortable', async () => {
      const {root} = mount({sortable: false});
      expect(root.querySelector('craft-reorder-button')).toBeNull();

      app?.unmount();
      container?.remove();

      const sortable = mount({sortable: true});
      expect(
        sortable.root.querySelector('craft-reorder-button')
      ).not.toBeNull();
    });

    it('emits reorder when a chip is moved', async () => {
      const {root, emitted} = mount({sortable: true});
      const button = root.querySelectorAll('craft-reorder-button')[1]!;

      button.dispatchEvent(
        new CustomEvent('reorder', {detail: {direction: 'up'}})
      );

      expect(emitted.reorder![0]).toEqual([1, 0]);
    });

    it('ignores a move that would fall off the end', async () => {
      const {root, emitted} = mount({sortable: true});
      const first = root.querySelectorAll('craft-reorder-button')[0]!;

      first.dispatchEvent(
        new CustomEvent('reorder', {detail: {direction: 'up'}})
      );

      expect(emitted.reorder).toHaveLength(0);
    });
  });

  describe('slots', () => {
    it('renders what a host puts in the suffix and append slots', async () => {
      const {root} = mount(
        {},
        {
          suffix: ({element}: {element: {id: number}}) =>
            h('span', {class: 'host-suffix'}, String(element.id)),
          append: ({element}: {element: {id: number}}) =>
            h('input', {type: 'hidden', value: String(element.id)}),
        }
      );

      expect(root.querySelectorAll('.host-suffix')).toHaveLength(3);
      expect(root.querySelectorAll('input[type="hidden"]')).toHaveLength(3);
    });
  });
});
