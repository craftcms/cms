import {beforeEach, expect, it} from 'vite-plus/test';
import './tabs.js';
import '../tab/tab.js';
import '../../styles/cp.css';

beforeEach(() => {
  document.body.innerHTML = '';
});

async function fixture(hostStyle: string): Promise<HTMLElement> {
  const tabs = document.createElement('craft-tabs');
  tabs.setAttribute('style', hostStyle);

  const tab = document.createElement('craft-tab');
  tab.slot = 'tab';
  tab.textContent = 'One';

  const panel = document.createElement('div');
  panel.slot = 'panel';
  panel.style.blockSize = '900px';

  tabs.append(tab, panel);
  document.body.append(tabs);

  await (tabs as HTMLElement & {updateComplete?: Promise<unknown>})
    .updateComplete;
  await new Promise((resolve) => requestAnimationFrame(() => resolve(null)));
  return tabs;
}

function panels(tabs: HTMLElement): HTMLElement {
  return tabs.shadowRoot!.querySelector<HTMLElement>('[part="panels"]')!;
}

type Strip = HTMLElement & {
  selectedIndex: number;
  updateComplete: Promise<unknown>;
};

async function strip({disabled = [] as number[]} = {}): Promise<{
  element: Strip;
  tabs: HTMLElement[];
  slottedPanels: HTMLElement[];
}> {
  const element = document.createElement('craft-tabs') as Strip;

  for (let index = 0; index < 3; index++) {
    const tab = document.createElement('craft-tab');
    tab.slot = 'tab';
    tab.textContent = `Tab ${index}`;
    tab.toggleAttribute('disabled', disabled.includes(index));

    const panel = document.createElement('div');
    panel.slot = 'panel';
    panel.textContent = `Panel ${index}`;

    element.append(tab, panel);
  }

  document.body.append(element);
  await element.updateComplete;

  const tabs = [...element.querySelectorAll<HTMLElement>('craft-tab')];
  await expect.poll(() => tabs[0]!.getAttribute('role')).toBe('tab');

  return {
    element,
    tabs,
    slottedPanels: [...element.querySelectorAll<HTMLElement>('[slot="panel"]')],
  };
}

it.each([
  {disabled: [], expected: 0},
  {disabled: [0], expected: 1},
])(
  'selects the first enabled tab when no selected-index is given (disabled: $disabled)',
  async ({disabled, expected}) => {
    const {element, tabs, slottedPanels} = await strip({disabled});

    await expect
      .poll(() => tabs.map((tab) => tab.getAttribute('aria-selected')))
      .toEqual(tabs.map((_, index) => String(index === expected)));
    expect(element.selectedIndex).toBe(expected);
    expect(
      slottedPanels.map((panel) => getComputedStyle(panel).display !== 'none')
    ).toEqual(slottedPanels.map((_, index) => index === expected));
  }
);

it('moves focus with the selection', async () => {
  const {element, tabs} = await strip();
  const {userEvent} = await import('@vitest/browser/context');

  tabs[0]!.focus();

  for (const [key, expected] of [
    ['{ArrowRight}', 1],
    ['{End}', 2],
    ['{ArrowRight}', 0],
    ['{ArrowLeft}', 2],
    ['{Home}', 0],
  ] as const) {
    await userEvent.keyboard(key);

    await expect.poll(() => element.selectedIndex).toBe(expected);
    expect(document.activeElement).toBe(tabs[expected]);
  }
});

it('tabs from the selected tab into its panel and back', async () => {
  const {element, tabs, slottedPanels} = await strip();
  const {userEvent} = await import('@vitest/browser/context');

  element.selectedIndex = 1;
  await element.updateComplete;
  tabs[1]!.focus();

  await userEvent.keyboard('{Tab}');
  expect(document.activeElement).toBe(slottedPanels[1]);

  await userEvent.keyboard('{Shift>}{Tab}{/Shift}');
  expect(document.activeElement).toBe(tabs[1]);
});

it('scrolls its panels inside a host that was given a height', async () => {
  const tabs = await fixture('display: block; block-size: 300px;');
  const region = panels(tabs);

  expect(Math.round(region.getBoundingClientRect().height)).toBeLessThanOrEqual(
    300
  );
  expect(region.scrollHeight).toBeGreaterThan(region.clientHeight);

  region.scrollTop = 500;
  expect(region.scrollTop).toBeGreaterThan(0);
});

it('grows with its panels under a host that was not', async () => {
  const tabs = await fixture('display: block;');
  const region = panels(tabs);

  expect(region.scrollHeight).toBe(region.clientHeight);
  expect(tabs.getBoundingClientRect().height).toBeGreaterThan(800);
});

it('scrolls its panels from the keyboard, leaving the strip in place', async () => {
  const tabs = await fixture('display: block; block-size: 300px;');
  const region = panels(tabs);
  const panel = tabs.querySelector<HTMLElement>('[slot="panel"]')!;
  const strip = tabs.shadowRoot!.querySelector<HTMLElement>('[part="strip"]')!;
  const stripTop = strip.getBoundingClientRect().top;

  expect(panel.tabIndex).toBe(0);

  panel.focus();
  const {userEvent} = await import('@vitest/browser/context');
  await userEvent.keyboard('{PageDown}');

  // The browser's own key scrolling is smooth, so it lands a frame or two on.
  await expect.poll(() => region.scrollTop).toBeGreaterThan(0);
  expect(strip.getBoundingClientRect().top).toBe(stripTop);
});
