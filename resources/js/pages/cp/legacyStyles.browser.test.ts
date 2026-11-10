import {afterEach, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser/context';
import '../../../css/cp.css';
import '../../../css/legacy.css';

afterEach(() => {
  document.body.classList.remove('cp-legacy-screen');
  document.documentElement.removeAttribute('dir');
  document.body.innerHTML = '';
});

it('draws legacy indicators, arrows and table headers', async () => {
  await page.viewport(1024, 720);
  document.body.classList.add('cp-legacy-screen');
  document.body.innerHTML = `
    <main style="padding: 24px">
      <table class="data">
        <thead><tr><th scope="col">Request</th><th scope="col">Status</th></tr></thead>
        <tbody><tr>
          <th scope="row">POST example.com</th>
          <td>
            <span class="status green"></span>
            <span class="status red"></span>
            <span class="status"></span>
            <a href="#edit" class="go" title="Edit"></a>
            <a href="#more" class="go">More</a>
          </td>
        </tr></tbody>
      </table>
      <div class="btngroup">
        <button type="submit" class="btn submit">Save</button>
        <button type="button" class="btn submit menubtn" aria-label="More actions"></button>
      </div>
      <div class="flex pagination">
        <nav class="flex" aria-label="Pagination">
          <button class="page-link prev-page disabled" aria-label="Previous Page" disabled></button>
          <a class="page-link next-page" href="#next" aria-label="Next Page"></a>
        </nav>
      </div>
    </main>`;

  const after = (selector: string) =>
    getComputedStyle(document.querySelector(selector)!, '::after');
  const box = (selector: string) =>
    document.querySelector(selector)!.getBoundingClientRect();

  for (const header of document.querySelectorAll('th')) {
    expect(getComputedStyle(header).textAlign).toBe('start');
  }

  expect(box('.status.green').width).toBeGreaterThan(0);
  expect(box('.status.green').height).toBe(box('.status.green').width);
  const green = getComputedStyle(document.querySelector('.status.green')!);
  const red = getComputedStyle(document.querySelector('.status.red')!);
  expect(green.backgroundColor).not.toBe('rgba(0, 0, 0, 0)');
  expect(green.backgroundColor).not.toBe(red.backgroundColor);
  expect(
    getComputedStyle(document.querySelector('.status:not(.green, .red)')!)
      .borderTopStyle
  ).toBe('solid');

  expect(box('.go[title]').width).toBeGreaterThanOrEqual(24);
  expect(box('.go[title]').height).toBeGreaterThanOrEqual(24);
  expect(after('.go[title]').content).not.toBe('none');
  expect(after('.go:not([title])').content).not.toBe('none');

  expect(box('.menubtn').top).toBe(box('.btngroup .submit:not(.menubtn)').top);
  expect(parseFloat(after('.menubtn').width)).toBeGreaterThan(0);
  expect(after('.menubtn').borderRightStyle).toBe('solid');

  for (const link of ['.prev-page', '.next-page']) {
    expect(box(link).width).toBeGreaterThanOrEqual(24);
    expect(box(link).height).toBeGreaterThanOrEqual(24);
    expect(parseFloat(after(link).width)).toBeGreaterThan(0);
  }
  const prevLtr = after('.prev-page').transform;
  const nextLtr = after('.next-page').transform;
  expect(prevLtr).not.toBe(nextLtr);

  document.documentElement.dir = 'rtl';
  expect(after('.prev-page').transform).toBe(nextLtr);
  expect(after('.next-page').transform).toBe(prevLtr);

  document.body.classList.remove('cp-legacy-screen');
  expect(box('.status.green').width).toBe(0);
  expect(after('.menubtn').borderRightStyle).toBe('none');
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
