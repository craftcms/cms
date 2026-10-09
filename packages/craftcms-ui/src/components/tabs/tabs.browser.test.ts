import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {contrast} from '../../../test/contrast';
import {sizes} from '@src/constants/size';
import './tabs.js';
import '../tab/tab.js';
import '../icon/icon.js';
import '../../styles/cp.css';

const themes = ['light', 'dark'];

beforeEach(() => {
  document.body.innerHTML = '';
});

async function fixture(hostStyle: string): Promise<HTMLElement> {
  const tabs = document.createElement('craft-tabs');
  tabs.setAttribute('style', hostStyle);
  tabs.setAttribute('label', 'Tabs');

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

async function strip({
  disabled = [] as number[],
  attrs = {} as Record<string, string>,
  icons = false,
  parent = document.body as HTMLElement,
} = {}): Promise<{
  element: Strip;
  tabs: HTMLElement[];
  slottedPanels: HTMLElement[];
}> {
  const element = document.createElement('craft-tabs') as Strip;
  element.setAttribute('label', 'Tabs');

  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }

  for (let index = 0; index < 3; index++) {
    const tab = document.createElement('craft-tab');
    tab.slot = 'tab';
    tab.toggleAttribute('disabled', disabled.includes(index));

    if (icons) {
      const icon = document.createElement('craft-icon');
      icon.setAttribute('name', 'circle-info');
      icon.setAttribute('label', `Tab ${index}`);
      tab.append(icon);
    } else {
      tab.textContent = `Tab ${index}`;
    }

    const panel = document.createElement('div');
    panel.slot = 'panel';
    panel.textContent = `Panel ${index}`;

    element.append(tab, panel);
  }

  parent.append(element);
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
  {disabled: [], attrs: {}, expected: 0},
  {disabled: [0], attrs: {}, expected: 1},
  {disabled: [], attrs: {'selected-index': '-1'}, expected: 0},
  {disabled: [0], attrs: {'selected-index': '-1'}, expected: 1},
  {disabled: [], attrs: {'selected-index': '5'}, expected: 0},
  {disabled: [1], attrs: {'selected-index': '1'}, expected: 0},
])(
  'selects the first enabled tab given no valid selected-index (disabled: $disabled, attrs: $attrs)',
  async ({disabled, attrs, expected}) => {
    const {element, tabs, slottedPanels} = await strip({disabled, attrs});

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

it('weights the selected tab the same as its reserved label width', async () => {
  const tabs = await fixture('display: block;');
  const tab = tabs.querySelector<HTMLElement>('craft-tab')!;
  const label = tab.shadowRoot!.querySelector<HTMLElement>('.tab__label')!;

  expect(tab.hasAttribute('selected')).toBe(true);
  expect(getComputedStyle(tab).fontWeight).toBe(
    getComputedStyle(label, '::after').fontWeight
  );
  expect(getComputedStyle(tab).fontWeight).not.toBe('700');
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

/** A surface in the given theme, for measuring contrast against. */
function surface(theme: string): HTMLElement {
  const wrapper = document.createElement('div');
  wrapper.dataset.theme = theme;
  wrapper.style.backgroundColor = 'var(--c-surface-default)';
  wrapper.style.padding = '2rem';
  document.body.append(wrapper);

  return wrapper;
}

it('keeps a focused panel’s ring inside the scrolling panel region', async () => {
  const tabs = await fixture('display: block; block-size: 300px;');
  const region = panels(tabs);
  const panel = tabs.querySelector<HTMLElement>('[slot="panel"]')!;
  const {userEvent} = await import('@vitest/browser/context');

  await expect.poll(() => panel.getAttribute('role')).toBe('tabpanel');
  tabs.querySelector<HTMLElement>('craft-tab')!.focus();
  await userEvent.keyboard('{Tab}');

  expect(document.activeElement).toBe(panel);
  expect(panel.matches(':focus-visible')).toBe(true);

  const style = getComputedStyle(panel);
  const reach =
    parseFloat(style.outlineOffset) + parseFloat(style.outlineWidth);
  const ring = panel.getBoundingClientRect();
  const clip = region.getBoundingClientRect();

  expect(style.outlineStyle).not.toBe('none');
  expect(ring.top - reach).toBeGreaterThanOrEqual(clip.top);
  expect(ring.left - reach).toBeGreaterThanOrEqual(clip.left);
  expect(ring.right + reach).toBeLessThanOrEqual(clip.right);
});

describe.each(themes)('in the %s theme', (theme) => {
  it('rings a keyboard-focused tab at 3:1 against the surface', async () => {
    const wrapper = surface(theme);
    const {tabs} = await strip({parent: wrapper});
    const {userEvent} = await import('@vitest/browser/context');

    await userEvent.keyboard('{Tab}');

    const tab = tabs[0]!;
    const style = getComputedStyle(tab);

    expect(document.activeElement).toBe(tab);
    expect(style.outlineStyle).toBe('solid');
    expect(parseFloat(style.outlineWidth)).toBeGreaterThanOrEqual(2);
    expect(
      contrast(style.outlineColor, getComputedStyle(wrapper).backgroundColor)
    ).toBeGreaterThanOrEqual(3);
  });

  it.each([
    {kind: 'text', icons: false, placement: 'block-start'},
    {kind: 'text', icons: false, placement: 'inline-start'},
    {kind: 'icon', icons: true, placement: 'block-start'},
    {kind: 'icon', icons: true, placement: 'inline-start'},
  ])(
    'marks the selected $kind tab ($placement) with a bar at 3:1, and only that tab',
    async ({icons, placement}) => {
      const wrapper = surface(theme);
      const {tabs} = await strip({parent: wrapper, icons, attrs: {placement}});
      const background = getComputedStyle(wrapper).backgroundColor;
      const bar = (tab: HTMLElement) => getComputedStyle(tab, '::after');

      await expect.poll(() => tabs[0]!.hasAttribute('selected')).toBe(true);

      const selected = bar(tabs[0]!);
      expect(parseFloat(selected.width)).toBeGreaterThan(0);
      expect(parseFloat(selected.height)).toBeGreaterThan(0);
      expect(
        contrast(selected.backgroundColor, background)
      ).toBeGreaterThanOrEqual(3);

      expect(bar(tabs[1]!).backgroundColor).toBe('rgba(0, 0, 0, 0)');
    }
  );
});

/** Whether a 24px circle centered on `rect` stays clear of every other target. */
function clearOfOthers(rect: DOMRect, others: DOMRect[]): boolean {
  const x = rect.left + rect.width / 2;
  const y = rect.top + rect.height / 2;

  return others.every((other) => {
    const dx = Math.max(other.left - x, 0, x - other.right);
    const dy = Math.max(other.top - y, 0, y - other.bottom);

    return Math.hypot(dx, dy) >= 12;
  });
}

describe.each(['block-start', 'inline-start'])('placed at %s', (placement) => {
  it.each([...sizes])(
    'gives every %s tab a 24px target, or room around it',
    async (size) => {
      const {tabs} = await strip({attrs: {placement, size}});
      const rects = tabs.map((tab) => tab.getBoundingClientRect());

      rects.forEach((rect, index) => {
        const bigEnough = rect.width >= 24 && rect.height >= 24;
        const others = rects.filter((_, other) => other !== index);

        expect(
          bigEnough || clearOfOthers(rect, others),
          `tab ${index}: ${rect.width}×${rect.height}`
        ).toBe(true);
      });
    }
  );
});

/** A strip driving three panels it doesn't slot, placed straight after it. */
async function externalStrip(): Promise<{
  element: Strip;
  tabs: HTMLElement[];
  sections: HTMLElement[];
}> {
  const element = document.createElement('craft-tabs') as Strip;
  element.setAttribute('label', 'Tabs');
  const sections: HTMLElement[] = [];

  for (let index = 0; index < 3; index++) {
    const tab = document.createElement('craft-tab');
    tab.slot = 'tab';
    tab.setAttribute('controls', `external-panel-${index}`);
    tab.textContent = `Tab ${index}`;
    element.append(tab);

    const section = document.createElement('section');
    section.id = `external-panel-${index}`;
    section.textContent = `Panel ${index}`;
    sections.push(section);
  }

  document.body.append(element, ...sections);
  await element.updateComplete;

  const tabs = [...element.querySelectorAll<HTMLElement>('craft-tab')];
  await expect.poll(() => tabs[0]!.getAttribute('role')).toBe('tab');

  return {element, tabs, sections};
}

it('moves the selection to the first tab when the selected slotted tab is removed', async () => {
  const {element, tabs, slottedPanels} = await strip();

  element.selectedIndex = 2;
  await element.updateComplete;
  tabs[2]!.remove();
  slottedPanels[2]!.remove();

  await expect.poll(() => element.selectedIndex).toBe(0);
  await expect.poll(() => tabs[0]!.getAttribute('aria-selected')).toBe('true');
  expect(getComputedStyle(slottedPanels[0]!).display).not.toBe('none');
});

it('moves the selection to the first tab when the selected external tab is removed', async () => {
  const {element, tabs, sections} = await externalStrip();

  element.selectedIndex = 2;
  await element.updateComplete;
  tabs[2]!.remove();
  sections[2]!.remove();

  await expect.poll(() => element.selectedIndex).toBe(0);
  await expect.poll(() => tabs[0]!.getAttribute('aria-selected')).toBe('true');
  expect(sections[0]!.classList.contains('hidden')).toBe(false);
});
