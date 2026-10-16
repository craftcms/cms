import '@craftcms/ui';
import {afterEach, expect, it} from 'vite-plus/test';
import {Tab} from './tab';

afterEach(() => {
  delete window.Craft;
  document.body.innerHTML = '';
});

it('makes the tab action button flush with the tab’s inline end', async () => {
  window.Craft = Object.assign(Object.create(null), {
    t: (_category: string, message: string) => message,
  });
  const tab = Object.create(Tab.prototype) as Tab;
  tab.$container = document.createElement('div');
  tab.$container.innerHTML = '<div class="tabs"><div class="tab"></div></div>';
  (tab as any).designer = {settings: {readOnly: false}};
  document.body.append(tab.$container);

  tab.createMenu();
  const menu = tab.$container.querySelector('craft-action-menu')!;
  await menu.updateComplete;

  expect(
    menu
      .querySelector(':scope > craft-button[slot="invoker"]')
      ?.getAttribute('flush')
  ).toBe('inline-end');
});
