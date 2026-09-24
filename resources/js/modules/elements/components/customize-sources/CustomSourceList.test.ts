import {type Component, createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import CustomSourceList from './CustomSourceList.vue';

interface Row {
  key: string;
  heading?: boolean;
}

// Two groups, each a heading over the sources beneath it.
const rows: Row[] = [
  {key: 'a', heading: true},
  {key: 'a1'},
  {key: 'a2'},
  {key: 'b', heading: true},
  {key: 'b1'},
];

/** A heading carries the rows beneath it, up to the next heading. */
function span(index: number): number {
  if (!rows[index]?.heading) return 1;

  const next = rows.findIndex((row, i) => i > index && row.heading);

  return (next === -1 ? rows.length : next) - index;
}

describe('CustomSourceList', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  beforeEach(() => {
    // Row icons fetch their SVGs; left in flight they're aborted at teardown.
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

  async function mount() {
    const reordered: Array<[number, number]> = [];

    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(CustomSourceList as Component, {
          items: rows,
          itemId: (row: Row) => row.key,
          label: (row: Row) => row.key,
          span,
          onReorder: (from: number, to: number) => reordered.push([from, to]),
        }),
    });
    app.mount(container);
    await nextTick();

    const handles = [...container.querySelectorAll('craft-reorder-button')];

    return {reordered, handles};
  }

  function press(handle: Element, direction: 'up' | 'down') {
    handle.dispatchEvent(
      new CustomEvent('craft-reorder', {detail: {direction}})
    );
  }

  it('moves a heading down past the row after its sources', async () => {
    const {reordered, handles} = await mount();

    press(handles[0]!, 'down');

    // a, a1, a2 move down one: `b` now leads, and the group starts at 1.
    expect(reordered).toEqual([[0, 1]]);
  });

  it('moves a heading up past the row before it', async () => {
    const {reordered, handles} = await mount();

    press(handles[3]!, 'up');

    expect(reordered).toEqual([[3, 2]]);
  });

  it('keeps a lone row moving alone', async () => {
    const {reordered, handles} = await mount();

    press(handles[1]!, 'down');

    expect(reordered).toEqual([[1, 2]]);
  });

  it('counts what a heading carries when placing its Move up/down', async () => {
    const {handles} = await mount();

    // The first group can't go up; the last one ends the list, so it can't go
    // down, even though its heading isn't the last row.
    expect(handles[0]!.getAttribute('position')).toBe('first');
    expect(handles[3]!.getAttribute('position')).toBe('last');
  });
});
