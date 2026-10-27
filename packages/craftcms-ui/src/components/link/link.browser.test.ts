import {beforeEach, expect, it, vi} from 'vite-plus/test';
import {userEvent} from 'vite-plus/test/browser';
import type CraftButton from '../button/button.js';
import type CraftPopover from '../popover/popover.js';
import type CraftTooltip from '../tooltip/tooltip.js';
import {linkHoverIntent} from '@src/utilities/hover-intent.js';
import type CraftLink from './link.js';
import './link.js';

let copied: string[] = [];

const hoverTiming = {...linkHoverIntent.options};

document.addEventListener('craft-copy', (event) => {
  copied.push((event as CustomEvent<{value: string}>).detail.value);
});

beforeEach(async () => {
  copied = [];
  linkHoverIntent.reset();
  linkHoverIntent.options = {...hoverTiming, warmUpDelay: 100};
  document.body.innerHTML = `
    <button type="button" class="before">Before</button>
    <div style="display: flex; gap: 2rem">
      <div style="width: 12rem">
        <craft-link>
          <a href="https://www.example.com/a/very/long/path/without/any/spaces">www.example.com/a/very/long/path/without/any/spaces</a>
        </craft-link>
      </div>
      <div style="width: 12rem">
        <craft-link class="second">
          <a href="https://www.example.org/another/very/long/path/without/spaces">www.example.org/another/very/long/path/without/spaces</a>
        </craft-link>
      </div>
    </div>
    <button type="button" class="after">After</button>
  `;
  await link().updateComplete;
});

function link(): CraftLink {
  return document.querySelector<CraftLink>('craft-link')!;
}

function secondLink(): CraftLink {
  return document.querySelector<CraftLink>('craft-link.second')!;
}

function popover(of: CraftLink = link()): CraftPopover {
  return of.shadowRoot!.querySelector<CraftPopover>('craft-popover')!;
}

// Its middle sits past the edge of the test viewport, cut off with the rest.
async function hoverSecondLink() {
  await userEvent.hover(secondLink().querySelector('a')!, {
    position: {x: 10, y: 5},
  });
}

function tooltip(id: string): CraftTooltip {
  return link().shadowRoot!.querySelector<CraftTooltip>(
    `craft-tooltip[for="${id}"]`
  )!;
}

function setHoverTiming(timing: Partial<typeof hoverTiming>) {
  linkHoverIntent.options = {...linkHoverIntent.options, ...timing};
}

function shadowPath(): string[] {
  const path: string[] = [];
  let active: Element | null = document.activeElement;
  while (active) {
    path.push(
      active.className
        ? `${active.localName}.${active.className}`
        : active.localName
    );
    active = active.shadowRoot?.activeElement ?? null;
  }
  return path;
}

async function settle() {
  await popover().updateComplete;
  await new Promise((resolve) => setTimeout(resolve, 50));
}

function wait(ms: number) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function hoverOpen() {
  await userEvent.hover(link().querySelector('a')!);
  await vi.waitFor(() => expect(popover().opened).toBe(true));
}

async function tabToDetailsButton() {
  document.querySelector<HTMLButtonElement>('.before')!.focus();
  await userEvent.keyboard('{Tab}');
}

it('clips the link to one line', () => {
  const text = link().shadowRoot!.querySelector<HTMLElement>('.text')!;

  expect(text.scrollWidth).toBeGreaterThan(text.clientWidth);
  expect(getComputedStyle(text).textOverflow).toBe('ellipsis');
});

it('reaches the details button, then the link, from the keyboard', async () => {
  document.querySelector<HTMLButtonElement>('.before')!.focus();
  await userEvent.keyboard('{Tab}');

  expect(shadowPath()).toEqual(['craft-link', 'craft-button']);

  await userEvent.keyboard('{Tab}');

  expect(document.activeElement).toBe(link().querySelector('a'));
  await wait(linkHoverIntent.options.warmUpDelay + 100);
  expect(popover().opened).toBe(false);
});

it('moves on to the link after the open panel', async () => {
  await tabToDetailsButton();
  await userEvent.keyboard('{Enter}');
  await settle();
  await userEvent.keyboard('{Tab}');
  await userEvent.keyboard('{Tab}');
  await userEvent.keyboard('{Tab}');
  await userEvent.keyboard('{Tab}');

  expect(document.activeElement).toBe(link().querySelector('a'));
});

it('opens the details with Enter, keeping focus on the button and copying nothing', async () => {
  await tabToDetailsButton();
  await userEvent.keyboard('{Enter}');
  await settle();

  expect(popover().opened).toBe(true);
  expect(shadowPath()).toEqual(['craft-link', 'craft-button']);
  expect(copied).toEqual([]);
});

it('moves through the open and copy buttons, then the full address, with Tab', async () => {
  await tabToDetailsButton();
  await userEvent.keyboard('{Enter}');
  await settle();

  await userEvent.keyboard('{Tab}');
  expect(shadowPath()).toEqual([
    'craft-link',
    'craft-button.details__open',
    'a.link',
  ]);

  await userEvent.keyboard('{Tab}');
  expect(shadowPath()).toEqual(['craft-link', 'craft-button.details__copy']);

  await userEvent.keyboard('{Tab}');
  expect(shadowPath()).toEqual(['craft-link', 'a.details__address']);
});

it('copies the full address', async () => {
  const writeText = vi
    .spyOn(navigator.clipboard, 'writeText')
    .mockResolvedValue(undefined);
  await tabToDetailsButton();
  await userEvent.keyboard('{Enter}');
  await settle();
  await userEvent.keyboard('{Tab}');
  await userEvent.keyboard('{Tab}');
  await userEvent.keyboard('{Enter}');

  await vi.waitFor(() => expect(copied).not.toEqual([]));
  expect(writeText).toHaveBeenCalledWith(
    'https://www.example.com/a/very/long/path/without/any/spaces'
  );
  expect(copied).toEqual([
    'https://www.example.com/a/very/long/path/without/any/spaces',
  ]);
  writeText.mockRestore();
});

it('shows and announces that the address was copied', async () => {
  const writeText = vi
    .spyOn(navigator.clipboard, 'writeText')
    .mockResolvedValue(undefined);
  const root = link().shadowRoot!;
  await popover().show();
  root.querySelector<HTMLElement>('.details__copy')!.click();

  await vi.waitFor(() => expect(copied).not.toEqual([]));
  await link().updateComplete;
  const icon = root.querySelector('.details__copy craft-icon')!;
  expect(icon.getAttribute('name')).toBe('check');
  expect(icon.getAttribute('label')).toBe('Copied');
  expect(root.querySelector('[role="status"]')!.textContent).toBe('Copied');
  writeText.mockRestore();
});

it('shows and announces that copying failed', async () => {
  const writeText = vi
    .spyOn(navigator.clipboard, 'writeText')
    .mockRejectedValue(new Error('denied'));
  const root = link().shadowRoot!;
  await popover().show();
  root.querySelector<HTMLElement>('.details__copy')!.click();

  const icon = root.querySelector('.details__copy craft-icon')!;
  await vi.waitFor(() => expect(icon.getAttribute('name')).toBe('xmark-large'));
  expect(icon.getAttribute('label')).toBe('Copy failed');
  expect(root.querySelector('[role="status"]')!.textContent).toBe(
    'Copy failed'
  );
  expect(copied).toEqual([]);
  writeText.mockRestore();
});

it('colors the details icon as a link', () => {
  const invoker = link().shadowRoot!.querySelector('[slot="invoker"]')!;
  const anchor = link().querySelector('a')!;

  expect(getComputedStyle(invoker).color).toBe(getComputedStyle(anchor).color);
});

it('shows the details button as hovered while the panel is open', async () => {
  await import('../../styles/shared/colorable.css');
  await import('../../styles/shared/variables.css');
  await import('../../styles/shared/tokens.css');
  setHoverTiming({warmUpDelay: 60_000});
  const invoker =
    link().shadowRoot!.querySelector<CraftButton>('[slot="invoker"]')!;
  const resting = getComputedStyle(invoker).backgroundColor;

  await userEvent.hover(invoker);
  const hovered = getComputedStyle(invoker).backgroundColor;
  await userEvent.hover(document.querySelector('.before')!);
  await tabToDetailsButton();
  await userEvent.keyboard('{Enter}');
  await settle();

  expect(hovered).not.toBe(resting);
  expect(getComputedStyle(invoker).backgroundColor).toBe(hovered);

  await userEvent.keyboard('{Escape}');
  await settle();

  expect(getComputedStyle(invoker).backgroundColor).toBe(resting);
});

it('closes with Escape and returns focus to the details button', async () => {
  await tabToDetailsButton();
  await userEvent.keyboard('{Enter}');
  await settle();
  await userEvent.keyboard('{Tab}');
  await vi.waitFor(() => expect(tooltip('open-button').opened).toBe(true));
  await userEvent.keyboard('{Escape}');
  await settle();

  expect(tooltip('open-button').opened).toBe(false);
  expect(popover().opened).toBe(true);

  await userEvent.keyboard('{Escape}');
  await settle();

  expect(popover().opened).toBe(false);
  expect(shadowPath()).toEqual(['craft-link', 'craft-button']);
});

it('shows a tooltip on each of the panel buttons', async () => {
  await popover().show();

  for (const [id, text] of [
    ['open-button', 'Open in a new tab'],
    ['copy-button', 'Copy'],
  ] as const) {
    await userEvent.hover(link().shadowRoot!.getElementById(id)!);
    await vi.waitFor(() => expect(tooltip(id).opened).toBe(true));
    expect(tooltip(id).textContent!.trim()).toBe(text);
  }
});

it("gives the panel's buttons at least a 24px target", async () => {
  await import('../../styles/shared/colorable.css');
  await import('../../styles/shared/variables.css');
  await import('../../styles/shared/tokens.css');
  await popover().show();

  for (const selector of ['.details__open', '.details__copy']) {
    const box = link()
      .shadowRoot!.querySelector(selector)!
      .getBoundingClientRect();
    expect(box.width).toBeGreaterThanOrEqual(24);
    expect(box.height).toBeGreaterThanOrEqual(24);
  }
});

it('opens the details once the pointer rests on the link for the warm-up', async () => {
  setHoverTiming({warmUpDelay: 300});
  await userEvent.hover(link().querySelector('a')!);
  await wait(150);

  expect(popover().opened).toBe(false);

  await vi.waitFor(() => expect(popover().opened).toBe(true));
  expect(document.activeElement).toBe(document.body);
});

it('opens the details when the link fits, too', async () => {
  link().parentElement!.style.width = '60rem';
  await userEvent.hover(link().querySelector('a')!);

  await vi.waitFor(() => expect(popover().opened).toBe(true));
});

it('leaves the details closed when the pointer moves off before the warm-up', async () => {
  setHoverTiming({warmUpDelay: 300});
  await userEvent.hover(link().querySelector('a')!);
  await userEvent.hover(document.querySelector('.before')!);
  await wait(400);

  expect(popover().opened).toBe(false);
});

it('ignores touch', async () => {
  setHoverTiming({warmUpDelay: 0});
  link().dispatchEvent(
    new PointerEvent('pointerenter', {pointerType: 'touch'})
  );
  await wait(100);

  expect(popover().opened).toBe(false);
});

it('keeps hover-opened details open while the pointer is on them', async () => {
  await hoverOpen();
  await userEvent.hover(
    link().shadowRoot!.querySelector<HTMLElement>('.details__address')!
  );
  await wait(400);

  expect(popover().opened).toBe(true);
});

it('closes hover-opened details once the pointer leaves', async () => {
  await hoverOpen();
  await userEvent.hover(document.querySelector('.before')!);

  await vi.waitFor(() => expect(popover().opened).toBe(false));
});

it('closes hover-opened details with Escape without moving focus', async () => {
  const before = document.querySelector<HTMLButtonElement>('.before')!;
  before.focus();
  await hoverOpen();
  await userEvent.keyboard('{Escape}');
  await settle();

  expect(popover().opened).toBe(false);
  expect(document.activeElement).toBe(before);
});

it('keeps hover-opened details open once the details button is clicked', async () => {
  await hoverOpen();
  await userEvent.click(
    link().shadowRoot!.querySelector<HTMLElement>('[slot="invoker"]')!
  );
  await userEvent.hover(document.querySelector('.before')!);
  await wait(400);

  expect(popover().opened).toBe(true);
});

it('opens other links straight away once warm, closing the last', async () => {
  await hoverOpen();
  setHoverTiming({warmUpDelay: 1000});
  await hoverSecondLink();
  await wait(100);

  expect(popover(secondLink()).opened).toBe(true);
  expect(popover().opened).toBe(false);
});

it('waits out the warm-up again once the cool-down passes', async () => {
  setHoverTiming({closeDelay: 0, coolDownDelay: 100});
  await hoverOpen();
  await userEvent.hover(document.querySelector('.before')!);
  await vi.waitFor(() => expect(popover().opened).toBe(false));
  await wait(200);
  setHoverTiming({warmUpDelay: 300});
  await hoverSecondLink();
  await wait(150);

  expect(popover(secondLink()).opened).toBe(false);
});

it('opens the details from the pointer resting on the details button too', async () => {
  await userEvent.hover(
    link().shadowRoot!.querySelector<HTMLElement>('[slot="invoker"]')!
  );

  await vi.waitFor(() => expect(popover().opened).toBe(true));
});

it('keeps the details within their panel inside a container that never wraps', async () => {
  link().parentElement!.style.whiteSpace = 'nowrap';
  await popover().show();
  const pane = popover()
    .shadowRoot!.querySelector('.popover-pane')!
    .getBoundingClientRect();

  for (const selector of ['.details__copy', '.details__address']) {
    const box = link()
      .shadowRoot!.querySelector(selector)!
      .getBoundingClientRect();
    expect(box.right).toBeLessThanOrEqual(pane.right);
  }
});

it('caps its width with --c-link-max-width', async () => {
  link().parentElement!.style.width = '60rem';
  link().style.setProperty('--c-link-max-width', '150px');

  expect(link().getBoundingClientRect().width).toBe(150);
});

it('caps a link in a table cell at 200px, unless the cell says otherwise', async () => {
  await import('../../styles/shared/base.css');
  document.body.innerHTML = `
    <table>
      <tr>
        <td>
          <craft-link>
            <a href="https://www.example.com/a/very/long/path/without/any/spaces/at/all">www.example.com/a/very/long/path/without/any/spaces/at/all</a>
          </craft-link>
        </td>
      </tr>
    </table>
  `;
  await link().updateComplete;

  expect(link().getBoundingClientRect().width).toBe(200);

  link().closest('td')!.style.setProperty('--c-link-max-width', '100%');

  expect(link().getBoundingClientRect().width).toBeGreaterThan(200);
});
