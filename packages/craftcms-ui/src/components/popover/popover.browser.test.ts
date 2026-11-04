import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser';
import type CraftButton from '../button/button.js';
import type CraftPopover from './popover.js';
import '../button/button.js';
import './popover.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('discloses a panel that is not a dialog', async () => {
  document.body.innerHTML = `
    <craft-popover>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content"><button type="button">Pick</button></div>
    </craft-popover>
  `;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  await popover.updateComplete;

  await expect
    .element(page.getByRole('button', {name: 'Open', expanded: false}))
    .toBeInTheDocument();

  await userEvent.click(page.getByRole('button', {name: 'Open'}));

  await expect
    .element(page.getByRole('button', {name: 'Open', expanded: true}))
    .toBeInTheDocument();
  await expect.element(page.getByRole('button', {name: 'Pick'})).toBeVisible();
  await expect.element(page.getByRole('dialog')).not.toBeInTheDocument();
});

describe('when focus moves', () => {
  async function openWith(markup: string): Promise<CraftPopover> {
    document.body.innerHTML = markup;
    const popover = document.querySelector<CraftPopover>('craft-popover')!;
    await popover.updateComplete;
    popover.opened = true;
    await popover.updateComplete;
    return popover;
  }

  it('stays open while focus moves within it', async () => {
    const popover = await openWith(`
      <craft-popover>
        <craft-button slot="invoker" type="button">Open</craft-button>
        <div slot="content">
          <button type="button">One</button>
          <button type="button">Two</button>
        </div>
      </craft-popover>
    `);

    page.getByRole('button', {name: 'One'}).element().focus();
    await userEvent.keyboard('{Tab}');
    await userEvent.keyboard('{Shift>}{Tab}{Tab}{/Shift}');
    await popover.updateComplete;

    expect(document.activeElement).toBe(
      popover.querySelector('[slot="invoker"]')
    );
    expect(popover.opened).toBe(true);
  });

  it('closes once focus leaves, leaving focus on the next control', async () => {
    const popover = await openWith(`
      <craft-popover>
        <craft-button slot="invoker" type="button">Open</craft-button>
        <div slot="content"><button type="button">Pick</button></div>
      </craft-popover>
      <button type="button">Next</button>
    `);

    page.getByRole('button', {name: 'Pick'}).element().focus();
    await userEvent.keyboard('{Tab}');

    await expect.poll(() => popover.opened).toBe(false);
    await expect
      .element(page.getByRole('button', {name: 'Next'}))
      .toHaveFocus();
  });

  it('stays open when focus moves to an invoker linked with for', async () => {
    const popover = await openWith(`
      <button type="button" id="trigger">Open</button>
      <craft-popover for="trigger">
        <div slot="content"><button type="button">Pick</button></div>
      </craft-popover>
    `);

    page.getByRole('button', {name: 'Pick'}).element().focus();
    document.getElementById('trigger')!.focus();
    await popover.updateComplete;

    expect(popover.opened).toBe(true);
  });

  it('stays open while focus is in a popover nested in its content', async () => {
    const outer = await openWith(`
      <craft-popover class="outer">
        <craft-button slot="invoker" type="button">Outer</craft-button>
        <div slot="content">
          <craft-popover class="inner">
            <craft-button slot="invoker" type="button">Inner</craft-button>
            <div slot="content"><button type="button">Deep</button></div>
          </craft-popover>
        </div>
      </craft-popover>
    `);
    const inner = outer.querySelector<CraftPopover>('.inner')!;
    await inner.updateComplete;
    inner.opened = true;
    await inner.updateComplete;

    page.getByRole('button', {name: 'Inner'}).element().focus();
    page.getByRole('button', {name: 'Deep'}).element().focus();
    await outer.updateComplete;

    expect(outer.opened).toBe(true);
    expect(inner.opened).toBe(true);
  });

  it('stays open when text in the panel is clicked', async () => {
    const popover = await openWith(`
      <craft-popover>
        <craft-button slot="invoker" type="button">Open</craft-button>
        <div slot="content">
          <p>Columns</p>
          <button type="button">Pick</button>
        </div>
      </craft-popover>
    `);

    page.getByRole('button', {name: 'Pick'}).element().focus();
    await userEvent.click(page.getByText('Columns'));
    await popover.updateComplete;

    expect(popover.opened).toBe(true);
  });
});

it('returns focus to the invoker, and stays closed, when Enter inside it calls hide()', async () => {
  document.body.innerHTML = `
    <craft-popover>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content"><button type="button" class="pick">Pick</button></div>
    </craft-popover>
  `;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const invoker = popover.querySelector<CraftButton>('craft-button')!;
  const pick = popover.querySelector<HTMLButtonElement>('.pick')!;
  pick.addEventListener('click', () => {
    void popover.hide();
  });
  await invoker.updateComplete;
  invoker.focus();
  await userEvent.keyboard('{Enter}');
  await popover.updateComplete;

  pick.focus();
  await userEvent.keyboard('{Enter}');
  await popover.updateComplete;

  expect(popover.opened).toBe(false);
  expect(document.activeElement).toBe(invoker);
});

it('returns focus to the invoker when Escape closes it from its slotted content', async () => {
  document.body.innerHTML = `
    <craft-popover>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content-body"><button type="button" class="pick">Pick</button></div>
    </craft-popover>
  `;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const invoker = popover.querySelector<CraftButton>('craft-button')!;
  await invoker.updateComplete;
  popover.opened = true;
  await popover.updateComplete;

  popover.querySelector<HTMLButtonElement>('.pick')!.focus();
  await userEvent.keyboard('{Escape}');
  await popover.updateComplete;

  expect(popover.opened).toBe(false);
  expect(document.activeElement).toBe(invoker);
});

it('closes and keeps focus on the invoker when Escape is pressed there', async () => {
  document.body.innerHTML = `
    <craft-popover>
      <craft-button slot="invoker" type="button">Open</craft-button>
      <div slot="content-body"><button type="button">Pick</button></div>
    </craft-popover>
  `;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const invoker = popover.querySelector<CraftButton>('craft-button')!;
  await invoker.updateComplete;
  invoker.focus();
  await userEvent.keyboard('{Enter}');
  await popover.updateComplete;

  await userEvent.keyboard('{Escape}');
  await popover.updateComplete;

  expect(popover.opened).toBe(false);
  expect(document.activeElement).toBe(invoker);
});

it('lets one Escape close the popover around it after it was closed another way', async () => {
  document.body.innerHTML = `
    <craft-popover class="outer">
      <craft-button slot="invoker" type="button">Outer</craft-button>
      <div slot="content-body">
        <craft-popover class="inner">
          <craft-button slot="invoker" type="button">Inner</craft-button>
          <div slot="content-body"><button type="button">Pick</button></div>
        </craft-popover>
      </div>
    </craft-popover>
  `;
  const outer = document.querySelector<CraftPopover>('.outer')!;
  const inner = document.querySelector<CraftPopover>('.inner')!;
  const outerInvoker = outer.querySelector<CraftButton>('craft-button')!;
  const innerInvoker = inner.querySelector<CraftButton>('craft-button')!;
  await outerInvoker.updateComplete;
  outerInvoker.focus();
  await userEvent.keyboard('{Enter}');
  await outer.updateComplete;
  await inner.show();
  await inner.hide();

  innerInvoker.focus();
  await userEvent.keyboard('{Escape}');
  await outer.updateComplete;

  expect(outer.opened).toBe(false);
  expect(document.activeElement).toBe(outerInvoker);
});

it('keeps Escape from reaching a container while it closes the popover', async () => {
  document.body.innerHTML = `
    <section>
      <craft-popover label="Context">
        <craft-button slot="invoker" type="button">Context</craft-button>
        <div slot="content"><label><input type="checkbox"> Title</label></div>
      </craft-popover>
    </section>`;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const escapes: boolean[] = [];
  document
    .querySelector('section')!
    .addEventListener('keydown', (event) =>
      escapes.push(event.defaultPrevented)
    );
  await popover.updateComplete;

  await userEvent.click(document.querySelector('craft-button')!);
  await expect.poll(() => popover.opened).toBe(true);
  popover.querySelector('input')!.focus();
  await userEvent.keyboard('{Escape}');

  await expect.poll(() => popover.opened).toBe(false);

  // Closed, the popover leaves Escape to the container.
  document.querySelector<HTMLElement>('craft-button')!.focus();
  await userEvent.keyboard('{Escape}');

  expect(escapes).toEqual([true, false]);
});

async function openTallPopover(
  wrapperStyle: string,
  popoverStyle = ''
): Promise<{pane: DOMRect; footer: DOMRect}> {
  document.body.innerHTML = `
    <div style="${wrapperStyle}">
      <craft-popover label="View" style="${popoverStyle}">
        <craft-button slot="invoker" type="button">View</craft-button>
        <div slot="content-body" style="block-size: 3000px">Columns</div>
        <div slot="content-footer"><button type="button">Close</button></div>
      </craft-popover>
    </div>`;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  await popover.updateComplete;
  await popover.show();

  const pane = popover.shadowRoot!.querySelector('.popover-pane')!;
  const footer = popover.querySelector('[slot="content-footer"]')!;
  await expect
    .poll(() => pane.getBoundingClientRect().height)
    .toBeGreaterThan(0);
  await new Promise((resolve) => requestAnimationFrame(resolve));

  return {
    pane: pane.getBoundingClientRect(),
    footer: footer.getBoundingClientRect(),
  };
}

it('grows to the room below its invoker, keeping its footer in view', async () => {
  const {pane, footer} = await openTallPopover('padding: 8px');

  expect(pane.height).toBeGreaterThan(window.innerHeight * 0.4);
  expect(pane.bottom).toBeCloseTo(window.innerHeight - 16, -1);
  expect(footer.bottom).toBeLessThanOrEqual(window.innerHeight);
});

it('grows to the room above its invoker when it opens upward', async () => {
  const {pane, footer} = await openTallPopover(
    'position: fixed; inset-block-end: 8px; inset-inline-start: 8px'
  );

  expect(pane.top).toBeCloseTo(16, -1);
  expect(pane.height).toBeGreaterThan(window.innerHeight * 0.4);
  expect(footer.bottom).toBeLessThanOrEqual(window.innerHeight);
});

it('keeps to an explicit --popover-max-block-size', async () => {
  const {pane} = await openTallPopover(
    'padding: 8px',
    '--popover-max-block-size: 120px'
  );

  expect(pane.height).toBe(120);
});
