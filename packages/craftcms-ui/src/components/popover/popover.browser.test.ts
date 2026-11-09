import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {page, userEvent} from 'vite-plus/test/browser';
import type CraftButton from '../button/button.js';
import type CraftPopover from './popover.js';
import '../button/button.js';
import './popover.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('gives the panel itself no role', async () => {
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

  it('stays open when focus moves to another button that controls its content', async () => {
    const popover = await openWith(`
      <a href="#" id="anchor">Settings</a>
      <button type="button" aria-controls="subnav">Show submenu</button>
      <craft-popover for="anchor">
        <div slot="content" id="subnav"><button type="button">Pick</button></div>
      </craft-popover>
    `);

    page.getByRole('button', {name: 'Pick'}).element().focus();
    page.getByRole('button', {name: 'Show submenu'}).element().focus();
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
      <craft-popover>
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
      <craft-popover style="${popoverStyle}">
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

function offsetFromInvoker(popover: CraftPopover, invoker: Element) {
  const pane = popover.shadowRoot!.querySelector('.popover-pane')!;
  const paneRect = pane.getBoundingClientRect();
  const invokerRect = invoker.getBoundingClientRect();

  return {
    x: Math.round(paneRect.left - invokerRect.left),
    y: Math.round(paneRect.top - invokerRect.bottom),
  };
}

it('opens below its invoker inside a transformed, clipping ancestor', async () => {
  document.body.innerHTML = `
    <div style="overflow: hidden; height: 60px; transform: translateZ(0); container-type: inline-size; margin: 40px">
      <craft-popover distance="4">
        <button slot="invoker" type="button">Open</button>
        <div slot="content" style="height: 150px">Content</div>
      </craft-popover>
    </div>`;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const invoker = popover.querySelector('button')!;

  await popover.show();

  expect(offsetFromInvoker(popover, invoker)).toEqual({x: 0, y: 4});
  const pane = popover.shadowRoot!.querySelector('.popover-pane')!;
  const rect = pane.getBoundingClientRect();
  // Painted on top rather than clipped by the 60px box.
  expect(
    pane.contains(
      popover.shadowRoot!.elementFromPoint(
        rect.left + 5,
        rect.bottom - 5
      ) as Node
    ) ||
      popover.contains(
        document.elementFromPoint(rect.left + 5, rect.bottom - 5) as Node
      )
  ).toBe(true);
});

it('follows its invoker when moved while open', async () => {
  document.body.innerHTML = `
    <div id="a" style="padding: 10px">
      <craft-popover distance="4">
        <button slot="invoker" type="button">Open</button>
        <div slot="content">Content</div>
      </craft-popover>
    </div>
    <div id="b" style="padding: 120px"></div>`;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const invoker = popover.querySelector('button')!;
  await popover.show();

  document.querySelector('#b')!.append(popover);
  await popover.updateComplete;
  await new Promise((resolve) => requestAnimationFrame(resolve));

  expect(popover.opened).toBe(true);
  expect(offsetFromInvoker(popover, invoker)).toEqual({x: 0, y: 4});
});

it('closes on a close-overlay from its content, without passing it on', async () => {
  document.body.innerHTML = `
    <craft-popover>
      <button slot="invoker" type="button">Open</button>
      <div slot="content"><button type="button" class="done">Done</button></div>
    </craft-popover>`;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const passedOn: Event[] = [];
  document.body.addEventListener('close-overlay', (event) =>
    passedOn.push(event)
  );
  await popover.show();

  popover
    .querySelector('.done')!
    .dispatchEvent(new Event('close-overlay', {bubbles: true, composed: true}));
  await popover.updateComplete;

  expect(popover.opened).toBe(false);
  expect(passedOn).toEqual([]);
});

it('moves to a new anchor that keeps the same context element while open', async () => {
  document.body.innerHTML = `
    <textarea></textarea>
    <craft-popover distance="4">
      <div slot="content">Content</div>
    </craft-popover>`;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const textarea = document.querySelector('textarea')!;
  popover.anchor = {
    contextElement: textarea,
    getBoundingClientRect: () => new DOMRect(20, 20, 0, 10),
  };
  await popover.show();

  popover.anchor = {
    contextElement: textarea,
    getBoundingClientRect: () => new DOMRect(200, 150, 0, 10),
  };
  await popover.updateComplete;
  await new Promise((resolve) => requestAnimationFrame(resolve));

  const pane = popover.shadowRoot!.querySelector('.popover-pane')!;
  const {left, top} = pane.getBoundingClientRect();
  expect({x: Math.round(left), y: Math.round(top)}).toEqual({x: 200, y: 164});
});

it('opens without an error when removed before a pending show() updates', async () => {
  document.body.innerHTML = `
    <craft-popover>
      <button slot="invoker" type="button">Open</button>
      <div slot="content">Content</div>
    </craft-popover>
    <div id="elsewhere"></div>`;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  await popover.updateComplete;

  const shown = popover.show();
  popover.remove();

  await expect(shown).resolves.toBeUndefined();

  document.querySelector('#elsewhere')!.append(popover);
  await popover.updateComplete;

  expect(
    popover
      .shadowRoot!.querySelector('.overlay-layer')!
      .matches(':popover-open')
  ).toBe(true);
});

it('treats a click on an invoker linked while open as inside, with manual set', async () => {
  document.body.innerHTML = `
    <button type="button" id="first">First</button>
    <button type="button" id="second">Second</button>
    <craft-popover manual for="first">
      <div slot="content">Content</div>
    </craft-popover>`;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  await popover.show();

  popover.for = 'second';
  await popover.updateComplete;
  await userEvent.click(page.getByRole('button', {name: 'Second'}));
  await new Promise((resolve) => setTimeout(resolve, 50));

  expect(popover.opened).toBe(true);
});

it.each([
  [
    'is linked to a wider invoker',
    (popover: CraftPopover) => (popover.for = 'wide'),
  ],
  [
    'has its invoker widened and is repositioned',
    (popover: CraftPopover) => {
      document.querySelector<HTMLElement>('#narrow')!.style.width = '300px';
      popover.reposition();
    },
  ],
])(
  'stays as wide as its invoker with match-invoker-width when it %s while open',
  async (_, change) => {
    document.body.innerHTML = `
      <button type="button" id="narrow" style="width: 100px">Narrow</button>
      <button type="button" id="wide" style="width: 300px">Wide</button>
      <craft-popover match-invoker-width for="narrow">
        <div slot="content">Hi</div>
      </craft-popover>`;
    const popover = document.querySelector<CraftPopover>('craft-popover')!;
    await popover.show();
    const pane = popover.shadowRoot!.querySelector('.popover-pane')!;
    expect(pane.getBoundingClientRect().width).toBe(100);

    change(popover);
    await popover.updateComplete;
    await new Promise((resolve) => requestAnimationFrame(resolve));

    expect(pane.getBoundingClientRect().width).toBe(300);
  }
);

it('follows an invoker linked while open when its scroll container scrolls', async () => {
  document.body.innerHTML = `
    <button type="button" id="first">First</button>
    <div id="scroller" style="height: 150px; overflow: auto">
      <div style="height: 400px; padding: 60px 20px 0">
        <button type="button" id="second">Second</button>
      </div>
    </div>
    <craft-popover distance="4" for="first">
      <div slot="content">Content</div>
    </craft-popover>`;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  const second = document.querySelector('#second')!;
  await popover.show();

  popover.for = 'second';
  await popover.updateComplete;
  await new Promise((resolve) => requestAnimationFrame(resolve));
  document.querySelector('#scroller')!.scrollTop = 40;

  await expect
    .poll(() => offsetFromInvoker(popover, second))
    .toEqual({x: 0, y: 4});
});

it('positions an open popover against an invoker slotted after it opened', async () => {
  document.body.innerHTML = `
    <div style="padding: 100px">
      <craft-popover distance="4">
        <div slot="content">Content</div>
      </craft-popover>
    </div>`;
  const popover = document.querySelector<CraftPopover>('craft-popover')!;
  await popover.show();

  const invoker = document.createElement('button');
  invoker.type = 'button';
  invoker.slot = 'invoker';
  invoker.textContent = 'Open';
  popover.prepend(invoker);

  await expect
    .poll(() => offsetFromInvoker(popover, invoker))
    .toEqual({x: 0, y: 4});
});

it('binds the invoker its for names in the shadow root it moves into', async () => {
  document.body.innerHTML = `<div id="a"></div><div id="b"></div>`;
  const rootA = document.querySelector('#a')!.attachShadow({mode: 'open'});
  const rootB = document.querySelector('#b')!.attachShadow({mode: 'open'});
  rootA.innerHTML = `
    <button type="button" id="trigger">A</button>
    <craft-popover for="trigger"><div slot="content">Content</div></craft-popover>`;
  rootB.innerHTML = `<button type="button" id="trigger">B</button>`;
  const popover = rootA.querySelector<CraftPopover>('craft-popover')!;
  const buttonA = rootA.querySelector('button')!;
  const buttonB = rootB.querySelector('button')!;
  await popover.updateComplete;
  expect(buttonA.getAttribute('aria-expanded')).toBe('false');

  rootB.append(popover);

  await expect.poll(() => buttonB.getAttribute('aria-expanded')).toBe('false');
  expect(buttonA.hasAttribute('aria-expanded')).toBe(false);

  buttonA.click();
  await popover.updateComplete;
  expect(popover.opened).toBe(false);

  buttonB.click();
  await popover.updateComplete;
  expect(popover.opened).toBe(true);
});
