import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import SelectableCardList from './SelectableCardList.vue';
import {
  useSelectable,
  type Selectable,
} from '@/common/composables/useSelectable';

describe('SelectableCardList', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    try {
      app?.unmount();
    } catch {
      // `craft-card` and friends relocate their own light DOM, which trips Vue's
      // unmount under happy-dom. Not what these tests are about.
    }
    container?.remove();
    app = undefined;
    container = undefined;
  });

  function mount(
    ids: string[],
    props: Record<string, unknown> = {}
  ): {selection: Selectable<string>; reorders: number[][]} {
    const selection = useSelectable<string>({ids: () => ids});
    const reorders: number[][] = [];
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      setup: () => () =>
        h(SelectableCardList, {
          ids,
          selection,
          selectable: true,
          sortable: true,
          onReorder: (from: number, to: number) => reorders.push([from, to]),
          ...props,
        } as never),
    });
    app.mount(container);

    return {selection, reorders};
  }

  it('renders a card per id, each with a select checkbox and drag handle', async () => {
    mount(['a', 'b', 'c']);
    await nextTick();

    expect(container!.querySelectorAll('li')).toHaveLength(3);
    expect(container!.querySelectorAll('craft-checkbox')).toHaveLength(3);
    expect(container!.querySelectorAll('.drag-handle')).toHaveLength(3);
  });

  /**
   * The way a browser does it: the click lands first (carrying the modifier
   * keys), then the checkbox settles and reports. `craft-checkbox` also re-fires
   * on programmatic `.checked` writes, so ordering these the other way round
   * moves the selection anchor before the shift state is known.
   */
  function check(box: Element, {shiftKey = false} = {}): void {
    box.dispatchEvent(new MouseEvent('click', {bubbles: true, shiftKey}));
    Object.assign(box, {checked: true});
    box.dispatchEvent(new CustomEvent('model-value-changed', {bubbles: true}));
  }

  it('selects through the checkbox and marks the item', async () => {
    const {selection} = mount(['a', 'b']);
    await nextTick();

    check(container!.querySelectorAll('craft-checkbox')[0]!);
    await nextTick();

    expect(selection.isSelected('a')).toBe(true);
    expect(container!.querySelectorAll('li')[0]!.className).toContain('sel');
  });

  it('extends the selection when the checkbox click was shifted', async () => {
    const {selection} = mount(['a', 'b', 'c']);
    await nextTick();

    const boxes = container!.querySelectorAll('craft-checkbox');
    check(boxes[0]!);
    await nextTick();
    check(boxes[2]!, {shiftKey: true});
    await nextTick();

    expect([...selection.selectedIds.value].sort()).toEqual(['a', 'b', 'c']);
  });

  it('reports a reorder from the handle button', async () => {
    const {reorders} = mount(['a', 'b', 'c']);
    await nextTick();

    container!
      .querySelectorAll('craft-reorder-button')[2]!
      .dispatchEvent(
        new CustomEvent('reorder', {bubbles: true, detail: {direction: 'up'}})
      );

    expect(reorders).toEqual([[2, 1]]);
  });

  it('leaves the selection alone when the click lands on a control', async () => {
    const {selection} = mount(['a', 'b']);
    await nextTick();

    // The checkbox reports through its own handler; the card's click must not
    // also fire and collapse the selection to that one item.
    const box = container!.querySelectorAll('craft-checkbox')[0]!;
    box.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    await nextTick();

    expect(selection.selectedIds.value).toEqual([]);
  });

  describe('keyboard', () => {
    function press(item: Element, key: string, init: KeyboardEventInit = {}) {
      item.dispatchEvent(
        // Cancelable, the way a real key press is — `preventDefault()` is how a
        // consumer takes the key.
        new KeyboardEvent('keydown', {
          key,
          bubbles: true,
          cancelable: true,
          ...init,
        })
      );
    }

    it('selects with Space and extends with a shifted arrow', async () => {
      const {selection} = mount(['a', 'b', 'c']);
      await nextTick();

      const items = container!.querySelectorAll('li');
      press(items[0]!, ' ');
      await nextTick();

      expect(selection.isSelected('a')).toBe(true);

      press(items[0]!, 'ArrowDown', {shiftKey: true});
      await nextTick();

      expect([...selection.selectedIds.value].sort()).toEqual(['a', 'b']);
    });

    it('leaves a key pressed inside the card alone', async () => {
      const {selection} = mount(['a']);
      await nextTick();

      // A Matrix block holds a whole form; Space in a text field is the
      // field's, not the list's.
      const input = document.createElement('input');
      container!.querySelector('li')!.append(input);
      press(input, ' ');
      await nextTick();

      expect(selection.selectedIds.value).toEqual([]);
    });

    it('stands down when the consumer takes the key', async () => {
      const {selection} = mount(['a'], {
        onItemKeydown: (_id: string, _index: number, event: KeyboardEvent) =>
          event.preventDefault(),
      });
      await nextTick();

      press(container!.querySelector('li')!, ' ');
      await nextTick();

      expect(selection.selectedIds.value).toEqual([]);
    });
  });

  it('passes per-item classes and attributes through', async () => {
    mount(['a'], {
      itemTag: 'div',
      itemClass: () => 'matrixblock',
      itemAttrs: (id: string) => ({'data-id': id, role: 'listitem'}),
    });
    await nextTick();

    const item = container!.querySelector('.matrixblock')!;

    expect(item.tagName.toLowerCase()).toBe('div');
    expect(item.getAttribute('data-id')).toBe('a');
    expect(item.getAttribute('role')).toBe('listitem');
  });
});
