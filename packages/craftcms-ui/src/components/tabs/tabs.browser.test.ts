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

  await (tabs as HTMLElement & {updateComplete?: Promise<unknown>}).updateComplete;
  await new Promise((resolve) => requestAnimationFrame(() => resolve(null)));
  return tabs;
}

function panels(tabs: HTMLElement): HTMLElement {
  return tabs.shadowRoot!.querySelector<HTMLElement>('[part="panels"]')!;
}

it('scrolls its panels inside a host that was given a height', async () => {
  const tabs = await fixture('display: block; block-size: 300px;');
  const region = panels(tabs);

  expect(Math.round(region.getBoundingClientRect().height)).toBeLessThanOrEqual(300);
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
