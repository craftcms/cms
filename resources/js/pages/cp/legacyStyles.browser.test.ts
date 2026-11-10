import {afterEach, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import '../../../css/cp.css';
import '../../../css/legacy.css';

afterEach(() => {
  document.body.classList.remove('cp-legacy-screen');
  document.body.innerHTML = '';
});

it('styles legacy plugin controls at desktop and narrow widths without leaking to modern screens', async () => {
  document.body.classList.add('cp-legacy-screen');
  document.body.innerHTML = `
    <main style="padding: 24px">
      <label for="name">Name</label>
      <input id="name" class="text fullwidth" value="Example">
      <label for="description">Description</label>
      <textarea id="description" class="text fullwidth">Example description</textarea>
      <div class="buttons">
        <a class="btn submit" href="#items">New item</a>
        <button class="btn" type="button">Preview</button>
        <button class="btn" type="button" disabled>Unavailable</button>
      </div>
      <div class="tablepane" id="items">
        <table class="data"><thead><tr><th scope="col">Name</th><th scope="col">Status</th></tr></thead>
          <tbody><tr><th scope="row">Example</th><td>Enabled</td></tr></tbody>
        </table>
      </div>
    </main>`;

  const input = document.querySelector<HTMLInputElement>('input')!;
  const textarea = document.querySelector<HTMLTextAreaElement>('textarea')!;
  const primary = document.querySelector<HTMLAnchorElement>('a')!;
  const secondary = document.querySelector<HTMLButtonElement>('button')!;
  const header = document.querySelector<HTMLTableCellElement>('thead th')!;

  for (const width of [1024, 320]) {
    await page.viewport(width, 720);
    expect(input.getBoundingClientRect().width).toBe(width - 48);
    expect(textarea.getBoundingClientRect().width).toBe(width - 48);
    expect(input.getBoundingClientRect().height).toBeGreaterThanOrEqual(32);
    expect(getComputedStyle(input).borderTopStyle).toBe('solid');
    expect(primary.getBoundingClientRect().height).toBeGreaterThanOrEqual(32);
    expect(getComputedStyle(primary).alignItems).toBe('center');
    expect(getComputedStyle(primary).backgroundColor).not.toBe(
      'rgba(0, 0, 0, 0)'
    );
    expect(getComputedStyle(primary).backgroundColor).not.toBe(
      getComputedStyle(secondary).backgroundColor
    );
    expect(
      parseFloat(getComputedStyle(header).paddingInlineStart)
    ).toBeGreaterThan(0);
    expect(getComputedStyle(header).backgroundColor).not.toBe(
      'rgba(0, 0, 0, 0)'
    );
    expect(document.documentElement.scrollWidth).toBe(width);
  }

  input.focus();
  await userEvent.keyboard('{Tab}');
  expect(document.activeElement).toBe(textarea);
  await userEvent.keyboard('{Tab}');
  expect(document.activeElement).toBe(primary);
  expect(getComputedStyle(primary).outlineStyle).toBe('solid');
  expect(
    parseFloat(getComputedStyle(primary).outlineWidth)
  ).toBeGreaterThanOrEqual(2);
  await userEvent.keyboard('{Tab}');
  expect(document.activeElement).toBe(secondary);
  await userEvent.keyboard('{Tab}');
  expect(document.activeElement).not.toBe(document.querySelector('[disabled]'));

  document.body.classList.remove('cp-legacy-screen');
  expect(input.getBoundingClientRect().width).toBeLessThan(272);
  expect(getComputedStyle(primary).backgroundColor).toBe('rgba(0, 0, 0, 0)');
});

it('lines a legacy table up with a modern one', async () => {
  document.body.classList.add('cp-legacy-screen');
  document.body.innerHTML = `
    <table class="data">
      <thead><tr><th scope="col">Name</th><th scope="col">Status</th><th class="thin"></th></tr></thead>
      <tbody>
        <tr><th scope="row">First</th><td>Enabled</td><td class="thin"><button type="button">Run</button></td></tr>
        <tr><th scope="row">Second</th><td>Disabled</td><td class="thin"><button type="button">Run</button></td></tr>
      </tbody>
    </table>`;

  const table = document.querySelector<HTMLTableElement>('table.data')!;
  const header = table.querySelector<HTMLTableCellElement>('thead th')!;
  const rowHeader = table.querySelector<HTMLTableCellElement>('tbody th')!;
  const rows = table.querySelectorAll<HTMLTableRowElement>('tbody tr');
  const firstRow = rows[0]!;
  const lastRow = rows[rows.length - 1]!;

  // Everything reads from the start edge — a `th` would centre on its own.
  for (const cell of table.querySelectorAll('th, td')) {
    expect(getComputedStyle(cell).textAlign).toBe('start');
  }

  // Cells sit flush, so the page doesn't show between them as a grid.
  expect(getComputedStyle(table).borderSpacing).toBe('0px');

  // One rule per row, and none under the last one.
  const rule = getComputedStyle(firstRow.cells[0]!);
  expect(parseFloat(rule.borderBlockEndWidth)).toBe(1);
  expect(rule.borderBlockEndColor).not.toBe('rgba(0, 0, 0, 0)');
  expect(
    parseFloat(getComputedStyle(lastRow.cells[0]!).borderBlockEndWidth)
  ).toBe(0);

  // A row header is set apart by weight, a step below the column header's.
  const rowWeight = Number(getComputedStyle(rowHeader).fontWeight);
  expect(rowWeight).toBeGreaterThan(400);
  expect(rowWeight).toBeLessThan(Number(getComputedStyle(header).fontWeight));

  // `.thin` columns shrink to their content instead of taking an even share.
  await page.viewport(1024, 720);
  expect(firstRow.cells[2]!.getBoundingClientRect().width).toBeLessThan(
    firstRow.cells[1]!.getBoundingClientRect().width
  );
});
