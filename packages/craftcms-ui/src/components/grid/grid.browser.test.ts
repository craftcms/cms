import {beforeEach, expect, it} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import type CraftGrid from './grid.js';
import type CraftButton from '../button/button.js';
import './grid.js';
import '../button/button.js';
import '../action-menu/action-menu.js';
import '../action-item/action-item.js';
import '../icon/icon.js';
import '../../styles/cp.css';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function render(): Promise<CraftGrid> {
  document.body.innerHTML = `
    <button id="before">Before</button>
    <h2 id="heading">Results</h2>
    <craft-grid aria-labelledby="heading">
      ${['one', 'two']
        .map(
          (name) => `
            <craft-grid-row>
              <craft-grid-cell><a href="#" id="${name}">Result ${name}</a></craft-grid-cell>
              <craft-grid-cell>
                <craft-action-menu>
                  <craft-button type="button" icon slot="invoker" data-menu="${name}">
                    <craft-icon name="ellipsis"></craft-icon>
                    <span class="sr-only">Actions for result ${name}</span>
                  </craft-button>
                  <div slot="content">
                    <craft-action-item id="${name}-edit">Edit</craft-action-item>
                    <craft-action-item id="${name}-delete">Delete</craft-action-item>
                  </div>
                </craft-action-menu>
              </craft-grid-cell>
            </craft-grid-row>
          `
        )
        .join('')}
    </craft-grid>
    <button id="after">After</button>
  `;
  const grid = document.querySelector<CraftGrid>('craft-grid')!;
  await grid.updateComplete;
  await customElements.whenDefined('craft-button');
  await new Promise((resolve) => setTimeout(resolve, 50));

  return grid;
}

function byId(id: string): HTMLElement {
  return document.getElementById(id)!;
}

/** The menu button in a result's row; the menu gives its invoker an id of its own. */
function menuButton(name: string): CraftButton {
  return document.querySelector<CraftButton>(`[data-menu="${name}"]`)!;
}

it('is one tab stop, which returns to the last focused cell', async () => {
  await render();

  byId('before').focus();
  await userEvent.keyboard('{Tab}');
  expect(document.activeElement).toBe(byId('one'));

  await userEvent.keyboard('{Tab}');
  expect(document.activeElement).toBe(byId('after'));

  await userEvent.keyboard('{Shift>}{Tab}{/Shift}');
  expect(document.activeElement).toBe(byId('one'));

  await userEvent.keyboard('{ArrowDown}{ArrowRight}');
  expect(document.activeElement).toBe(menuButton('two'));

  byId('before').focus();
  await userEvent.keyboard('{Tab}');
  expect(document.activeElement).toBe(menuButton('two'));
});

it('moves between a link and a menu button with the arrow keys', async () => {
  await render();

  byId('one').focus();
  await userEvent.keyboard('{ArrowRight}');
  expect(document.activeElement).toBe(menuButton('one'));

  await userEvent.keyboard('{ArrowDown}');
  expect(document.activeElement).toBe(menuButton('two'));

  await userEvent.keyboard('{ArrowLeft}');
  expect(document.activeElement).toBe(byId('two'));
});

it('leaves arrow keys in an open menu to the menu', async () => {
  await render();

  byId('one').focus();
  await userEvent.keyboard('{ArrowRight}{Enter}');
  await new Promise((resolve) => setTimeout(resolve, 50));
  const focused = document.activeElement;
  expect(focused?.closest('[slot="content"]')).not.toBeNull();

  await userEvent.keyboard('{ArrowDown}');
  expect(document.activeElement?.closest('craft-grid-row')).toBe(
    byId('one').closest('craft-grid-row')
  );
  expect(document.activeElement?.closest('[slot="content"]')).not.toBeNull();
});

it('keeps a craft-button out of the tab order when it resets its own tabindex', async () => {
  await render();
  const menu = menuButton('two');

  menu.disabled = true;
  await menu.updateComplete;
  menu.disabled = false;
  await menu.updateComplete;
  await new Promise((resolve) => setTimeout(resolve));

  expect(menu.getAttribute('tabindex')).toBe('-1');

  byId('before').focus();
  await userEvent.keyboard('{Tab}{Tab}');
  expect(document.activeElement).toBe(byId('after'));
});
