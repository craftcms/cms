import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftGrid from './grid.js';
import './grid.js';

beforeEach(() => {
  document.body.innerHTML = '';
  document.documentElement.removeAttribute('dir');
});

/** A grid of `rows` rows, each holding a link per entry in `columns`. */
async function render(markup: string): Promise<CraftGrid> {
  document.body.innerHTML = markup;
  const grid = document.querySelector<CraftGrid>('craft-grid')!;
  await grid.updateComplete;
  await settle();

  return grid;
}

function linkGrid(rows: number, columns = 2): string {
  const body = Array.from(
    {length: rows},
    (_, row) =>
      `<craft-grid-row>${Array.from(
        {length: columns},
        (_, column) =>
          `<craft-grid-cell><a href="#" id="r${row}c${column}">Cell ${row} ${column}</a></craft-grid-cell>`
      ).join('')}</craft-grid-row>`
  ).join('');

  return `<craft-grid label="Results">${body}</craft-grid>`;
}

/** Lets the grid's mutation observer run. */
async function settle(): Promise<void> {
  await new Promise((resolve) => setTimeout(resolve));
}

function byId(id: string): HTMLElement {
  return document.getElementById(id)!;
}

function press(key: string, init: KeyboardEventInit = {}): KeyboardEvent {
  const target = document.activeElement as HTMLElement;
  const event = new KeyboardEvent('keydown', {
    key,
    bubbles: true,
    cancelable: true,
    composed: true,
    ...init,
  });
  target.dispatchEvent(event);

  return event;
}

function tabStops(grid: CraftGrid): string[] {
  return [...grid.querySelectorAll<HTMLElement>('[tabindex="0"]')].map(
    (element) => element.id
  );
}

describe('craft-grid', () => {
  it('exposes grid, row and gridcell roles', async () => {
    const grid = await render(linkGrid(1, 1));

    expect(grid.getAttribute('role')).toBe('grid');
    expect(grid.querySelector('craft-grid-row')!.getAttribute('role')).toBe(
      'row'
    );
    expect(grid.querySelector('craft-grid-cell')!.getAttribute('role')).toBe(
      'gridcell'
    );
  });

  it('keeps a role given to a row or cell, and slots their content', async () => {
    const grid = await render(`
      <craft-grid label="Results">
        <craft-grid-row role="presentation">
          <craft-grid-cell role="rowheader"><a href="#">Link</a></craft-grid-cell>
        </craft-grid-row>
      </craft-grid>
    `);
    const row = grid.querySelector('craft-grid-row')!;
    const cell = grid.querySelector('craft-grid-cell')!;
    await row.updateComplete;
    await cell.updateComplete;

    expect(row.getAttribute('role')).toBe('presentation');
    expect(cell.getAttribute('role')).toBe('rowheader');
    expect(grid.shadowRoot!.querySelector('slot:not([name])')).not.toBeNull();
    expect(row.shadowRoot!.querySelector('slot:not([name])')).not.toBeNull();
    expect(cell.shadowRoot!.querySelector('slot:not([name])')).not.toBeNull();
  });

  it('names the grid from label', async () => {
    const grid = await render(linkGrid(1));

    expect(grid.getAttribute('aria-label')).toBe('Results');

    grid.label = null;
    await grid.updateComplete;

    expect(grid.hasAttribute('aria-label')).toBe(false);
  });

  it('leaves a nested grid’s rows to the nested grid', async () => {
    const grid = await render(`
      <craft-grid label="Outer">
        <craft-grid-row>
          <craft-grid-cell><a href="#" id="outer">Outer</a></craft-grid-cell>
          <craft-grid-cell id="outer-cell">
            <craft-grid label="Inner">
              <craft-grid-row>
                <craft-grid-cell><a href="#" id="inner">Inner</a></craft-grid-cell>
              </craft-grid-row>
            </craft-grid>
          </craft-grid-cell>
        </craft-grid-row>
      </craft-grid>
    `);

    expect(tabStops(grid)).toEqual(['outer', 'inner']);

    byId('outer').focus();
    press('ArrowRight');

    expect(document.activeElement).toBe(byId('outer-cell'));
  });

  it('keeps one tab stop, on the first cell until another takes focus', async () => {
    const grid = await render(linkGrid(2));

    expect(tabStops(grid)).toEqual(['r0c0']);
    expect(byId('r1c1').getAttribute('tabindex')).toBe('-1');

    byId('r1c1').focus();
    await settle();

    expect(tabStops(grid)).toEqual(['r1c1']);
  });

  it('focuses a cell with no interactive element itself', async () => {
    const grid = await render(`
      <craft-grid label="Results">
        <craft-grid-row>
          <craft-grid-cell id="text">Just text</craft-grid-cell>
          <craft-grid-cell><button id="button">Go</button></craft-grid-cell>
        </craft-grid-row>
      </craft-grid>
    `);

    expect(tabStops(grid)).toEqual(['text']);

    byId('text').focus();
    press('ArrowRight');

    expect(document.activeElement).toBe(byId('button'));
  });

  it('leaves controls inside a cell’s popup out', async () => {
    const grid = await render(`
      <craft-grid label="Results">
        <craft-grid-row>
          <craft-grid-cell><a href="#" id="link">Link</a></craft-grid-cell>
          <craft-grid-cell>
            <div id="menu">
              <button id="invoker" slot="invoker">Actions</button>
              <div slot="content"><button id="item">Delete</button></div>
            </div>
          </craft-grid-cell>
        </craft-grid-row>
      </craft-grid>
    `);

    byId('link').focus();
    press('ArrowRight');

    expect(document.activeElement).toBe(byId('invoker'));
    expect(byId('item').hasAttribute('tabindex')).toBe(false);
    expect(tabStops(grid)).toEqual(['invoker']);
  });

  it('moves along a row with Right and Left, stopping at the ends', async () => {
    await render(linkGrid(1, 3));

    byId('r0c0').focus();
    press('ArrowLeft');
    expect(document.activeElement).toBe(byId('r0c0'));

    press('ArrowRight');
    press('ArrowRight');
    expect(document.activeElement).toBe(byId('r0c2'));

    press('ArrowRight');
    expect(document.activeElement).toBe(byId('r0c2'));

    press('ArrowLeft');
    expect(document.activeElement).toBe(byId('r0c1'));
  });

  it('mirrors Right and Left in a right-to-left layout', async () => {
    document.documentElement.setAttribute('dir', 'rtl');
    await render(linkGrid(1, 3));

    byId('r0c0').focus();
    press('ArrowLeft');
    expect(document.activeElement).toBe(byId('r0c1'));

    press('ArrowRight');
    expect(document.activeElement).toBe(byId('r0c0'));
  });

  it('moves between rows with Down and Up, keeping the column where it can', async () => {
    await render(`
      <craft-grid label="Results">
        <craft-grid-row>
          <craft-grid-cell><a href="#" id="a0">A0</a></craft-grid-cell>
          <craft-grid-cell><a href="#" id="a1">A1</a></craft-grid-cell>
        </craft-grid-row>
        <craft-grid-row>
          <craft-grid-cell><a href="#" id="b0">B0</a></craft-grid-cell>
        </craft-grid-row>
        <craft-grid-row>
          <craft-grid-cell><a href="#" id="c0">C0</a></craft-grid-cell>
          <craft-grid-cell><a href="#" id="c1">C1</a></craft-grid-cell>
        </craft-grid-row>
      </craft-grid>
    `);

    byId('a1').focus();
    press('ArrowUp');
    expect(document.activeElement).toBe(byId('a1'));

    press('ArrowDown');
    expect(document.activeElement).toBe(byId('b0'));

    press('ArrowDown');
    expect(document.activeElement).toBe(byId('c0'));

    press('ArrowDown');
    expect(document.activeElement).toBe(byId('c0'));
  });

  it('jumps to the ends of the row with Home and End, and of the grid with Control', async () => {
    await render(linkGrid(3, 3));

    byId('r1c1').focus();
    press('End');
    expect(document.activeElement).toBe(byId('r1c2'));

    press('Home');
    expect(document.activeElement).toBe(byId('r1c0'));

    press('End', {ctrlKey: true});
    expect(document.activeElement).toBe(byId('r2c2'));

    press('Home', {metaKey: true});
    expect(document.activeElement).toBe(byId('r0c0'));
  });

  it('moves five rows with Page Down and Page Up, stopping at the ends', async () => {
    await render(linkGrid(8, 1));

    byId('r0c0').focus();
    press('PageDown');
    expect(document.activeElement).toBe(byId('r5c0'));

    press('PageDown');
    expect(document.activeElement).toBe(byId('r7c0'));

    press('PageUp');
    expect(document.activeElement).toBe(byId('r2c0'));

    press('PageUp');
    expect(document.activeElement).toBe(byId('r0c0'));
  });

  it('skips hidden rows, hidden cells and disabled controls', async () => {
    await render(`
      <craft-grid label="Results">
        <craft-grid-row>
          <craft-grid-cell><a href="#" id="first">First</a></craft-grid-cell>
          <craft-grid-cell hidden><a href="#" id="hidden-cell">Hidden</a></craft-grid-cell>
          <craft-grid-cell><button id="disabled" disabled>Off</button><a href="#" id="last">Last</a></craft-grid-cell>
        </craft-grid-row>
        <craft-grid-row hidden>
          <craft-grid-cell><a href="#" id="hidden-row">Hidden</a></craft-grid-cell>
        </craft-grid-row>
        <craft-grid-row>
          <craft-grid-cell><a href="#" id="below">Below</a></craft-grid-cell>
        </craft-grid-row>
      </craft-grid>
    `);

    byId('first').focus();
    press('ArrowRight');
    expect(document.activeElement).toBe(byId('last'));

    press('ArrowDown');
    expect(document.activeElement).toBe(byId('below'));
  });

  it('keeps the tab stop in place when the row holding it is removed', async () => {
    const grid = await render(linkGrid(3));

    byId('r1c1').focus();
    await settle();
    byId('r1c1').closest('craft-grid-row')!.remove();
    await settle();

    expect(tabStops(grid)).toEqual(['r2c1']);

    grid.replaceChildren();
    grid.insertAdjacentHTML(
      'beforeend',
      '<craft-grid-row><craft-grid-cell><a href="#" id="fresh">Fresh</a></craft-grid-cell></craft-grid-row>'
    );
    await settle();

    expect(tabStops(grid)).toEqual(['fresh']);
  });

  it('puts the tab stop back on the first cell when asked to', async () => {
    const grid = await render(linkGrid(3));

    byId('r2c1').focus();
    await settle();
    expect(tabStops(grid)).toEqual(['r2c1']);

    grid.resetTabStop();

    expect(tabStops(grid)).toEqual(['r0c0']);
  });

  it('takes a control that resets its own tabindex back out of the tab order', async () => {
    const grid = await render(linkGrid(2));

    byId('r1c0').setAttribute('tabindex', '0');
    await settle();

    expect(tabStops(grid)).toEqual(['r0c0']);
  });

  it('leaves keys in a text field to the field', async () => {
    await render(`
      <craft-grid label="Results">
        <craft-grid-row>
          <craft-grid-cell><input id="field" type="text"></craft-grid-cell>
          <craft-grid-cell><a href="#" id="link">Link</a></craft-grid-cell>
        </craft-grid-row>
      </craft-grid>
    `);

    byId('field').focus();
    const event = press('ArrowRight');

    expect(event.defaultPrevented).toBe(false);
    expect(document.activeElement).toBe(byId('field'));
  });

  it('leaves keys pressed inside a cell’s popup to the popup', async () => {
    await render(`
      <craft-grid label="Results">
        <craft-grid-row>
          <craft-grid-cell>
            <button id="invoker" slot="invoker">Actions</button>
            <div slot="content"><button id="item">Delete</button></div>
          </craft-grid-cell>
        </craft-grid-row>
        <craft-grid-row>
          <craft-grid-cell><a href="#" id="below">Below</a></craft-grid-cell>
        </craft-grid-row>
      </craft-grid>
    `);

    byId('item').focus();
    const event = press('ArrowDown');

    expect(event.defaultPrevented).toBe(false);
    expect(document.activeElement).toBe(byId('item'));
  });

  it('ignores keys another handler has already taken', async () => {
    const grid = await render(linkGrid(2));
    byId('r0c0').addEventListener('keydown', (event) => event.preventDefault());

    byId('r0c0').focus();
    press('ArrowDown');

    expect(document.activeElement).toBe(byId('r0c0'));
    expect(tabStops(grid)).toEqual(['r0c0']);
  });

  it('gives controls back their own tabindex when it is removed', async () => {
    const grid = await render(`
      <craft-grid label="Results">
        <craft-grid-row>
          <craft-grid-cell><a href="#" id="link">Link</a></craft-grid-cell>
          <craft-grid-cell><span id="custom" tabindex="0" role="button">Custom</span></craft-grid-cell>
          <craft-grid-cell id="cell">Text</craft-grid-cell>
        </craft-grid-row>
      </craft-grid>
    `);
    const link = byId('link');
    const custom = byId('custom');
    const cell = byId('cell');

    expect(custom.getAttribute('tabindex')).toBe('-1');
    expect(cell.getAttribute('tabindex')).toBe('-1');

    grid.remove();

    expect(link.hasAttribute('tabindex')).toBe(false);
    expect(custom.getAttribute('tabindex')).toBe('0');
    expect(cell.hasAttribute('tabindex')).toBe(false);
  });
});
