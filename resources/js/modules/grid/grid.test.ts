import {afterEach, expect, it} from 'vite-plus/test';
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
