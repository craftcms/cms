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
