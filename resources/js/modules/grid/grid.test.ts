import {afterEach, expect, it, vi} from 'vite-plus/test';
import {Grid} from './grid';

afterEach(() => {
  document.body.innerHTML = '';
});

it('tracks its items in document order without jQuery', () => {
  document.body.innerHTML = `
    <div id="grid">
      <div class="item" id="a"></div>
      <div class="other"></div>
      <div class="item" id="b"></div>
    </div>`;
  const container = document.getElementById('grid')!;
  const grid = new Grid(container);

  expect(grid.items.map((item) => item.id)).toEqual(['a', 'b']);

  const c = document.createElement('div');
  c.id = 'c';
  container.prepend(c);
  grid.addItems([document.getElementById('b')!, c]);
  expect(grid.items.map((item) => item.id)).toEqual(['c', 'a', 'b']);

  grid.removeItems(document.getElementById('a'));
  expect(grid.items.map((item) => item.id)).toEqual(['c', 'b']);
});

it('sizes an item spanning several columns to reach the last of them', () => {
  document.body.innerHTML = '<div id="grid"><div class="item"></div></div>';
  const grid = new Grid(document.getElementById('grid')!, {gutter: 14});
  grid.totalCols = 3;
  grid.colPctWidth = 100 / 3;
  grid.colGutterDrop = (14 * 2) / 3;

  // Spanning every column, it reaches the far edge.
  expect(grid.getItemWidthCss(3)).toBe('calc(100% - 0px)');

  // n columns plus the n - 1 gutters between them, out of 3 columns and 2 gutters.
  expect(grid.getItemWidthDrop(1)).toBeCloseTo(28 / 3);
  expect(grid.getItemWidthDrop(2)).toBeCloseTo(14 / 3);
  expect(grid.getItemWidthDrop(3)).toBe(0);
});

it('coalesces queued refreshes into one, keeping any force', async () => {
  document.body.innerHTML = '<div id="grid"><div class="item"></div></div>';
  const grid = new Grid(document.getElementById('grid')!);
  const refreshCols = vi.spyOn(grid, 'refreshCols');

  grid.queueRefreshCols();
  grid.queueRefreshCols(true);
  grid.queueRefreshCols();
  expect(refreshCols).not.toHaveBeenCalled();

  await Promise.resolve();
  expect(refreshCols).toHaveBeenCalledTimes(1);
  expect(refreshCols).toHaveBeenCalledWith(true);

  grid.queueRefreshCols();
  await Promise.resolve();
  expect(refreshCols).toHaveBeenCalledTimes(2);
  expect(refreshCols).toHaveBeenLastCalledWith(false);
});

it('drops a queued refresh once the grid is destroyed', async () => {
  document.body.innerHTML = '<div id="grid"><div class="item"></div></div>';
  const grid = new Grid(document.getElementById('grid')!);
  const refreshCols = vi.spyOn(grid, 'refreshCols');

  grid.queueRefreshCols(true);
  grid.destroy();
  await Promise.resolve();

  expect(refreshCols).not.toHaveBeenCalled();
});

it('measures every item before sizing any when equalizing rows', () => {
  const naturalHeights = [30, 50, 20, 10, 40];
  document.body.innerHTML = `<div id="grid">${naturalHeights
    .map(() => '<div class="item"></div>')
    .join('')}</div>`;
  const container = document.getElementById('grid')!;
  Object.defineProperty(container, 'scrollHeight', {value: 100});
  Object.defineProperty(container, 'clientWidth', {value: 900});

  const log: string[] = [];
  const items = Array.from(container.children) as HTMLElement[];
  items.forEach((item, i) => {
    let height = '';
    Object.defineProperty(item.style, 'height', {
      get: () => height,
      set: (value: string) => {
        log.push('write');
        height = value;
      },
    });
    Object.defineProperty(item, 'clientHeight', {
      get: () => {
        log.push('read');
        return height === 'auto' ? naturalHeights[i] : parseFloat(height);
      },
    });
  });

  new Grid(container, {minColWidth: 300, fillMode: 'grid', snapToGrid: 16});

  expect(items.map((item) => item.style.height)).toEqual([
    '64px',
    '64px',
    '64px',
    '48px',
    '48px',
  ]);
  expect(log.join(' ')).toMatch(/^(write )+(read )+(write ?)+$/);
});
