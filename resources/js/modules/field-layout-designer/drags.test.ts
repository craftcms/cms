import {afterEach, expect, it, vi} from 'vite-plus/test';
import $ from 'jquery';
import {TabDrag} from './drags';

afterEach(() => {
  delete window.$;
  document.body.innerHTML = '';
});

it('lets tabs be dropped at the end of either column', () => {
  window.$ = $;
  const $content = document.createElement('div');
  const $sidebar = document.createElement('div');
  document.body.append($content, $sidebar);
  const tabGrid = {addItems: vi.fn(), removeItems: vi.fn()};

  const drag = Object.create(TabDrag.prototype) as TabDrag;
  Object.assign(drag, {
    designer: {
      $contentTabContainer: $content,
      $sidebarTabContainer: $sidebar,
      tabGrid,
    },
    $items: [],
  });

  const cabooses = drag.createCaboose();

  expect(cabooses.map(($caboose) => $caboose.parentElement)).toEqual([
    $content,
    $sidebar,
  ]);
  // Only the content column's caboose joins the grid.
  expect(tabGrid.addItems).toHaveBeenCalledOnce();
  expect(tabGrid.addItems.mock.calls[0]![0].get(0)).toBe(cabooses[0]);
});
