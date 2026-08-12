import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftTab from '../tab/tab.js';
import type CraftTabs from './tabs.js';
import styles from './tabs.styles.js';
import '../tab/tab.js';
import './tabs.js';

/*
 * SCOPE: this covers what `CraftTabs` adds on top of `LionTabs` — the wrapper
 * and parts, the `layout` axis, and the tab's own state.
 *
 * Lion's selection machinery is NOT exercised here, and can't be: it bootstraps
 * from a `slotchange` on the shadow tab slot, and happy-dom never fires one
 * (nodes are assigned — `assignedNodes()` is correct — but the event isn't
 * dispatched). Nothing gets wired, so every selection assertion would fail
 * against a component that works fine in a browser. Selection, keyboard
 * navigation, and disabled-tab skipping are covered by the play functions in
 * tabs.stories.ts, which run in real Chromium.
 */

/** Builds a strip of `count` tabs and matching panels. */
async function createTabs({
  count = 3,
  attrs = {} as Record<string, string>,
} = {}): Promise<CraftTabs> {
  const element = document.createElement('craft-tabs') as CraftTabs;

  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }

  for (let i = 0; i < count; i++) {
    const tab = document.createElement('craft-tab');
    tab.slot = 'tab';
    tab.textContent = `Tab ${i}`;

    const panel = document.createElement('div');
    panel.slot = 'panel';
    panel.textContent = `Panel ${i}`;

    element.append(tab, panel);
  }

  document.body.append(element);
  await element.updateComplete;

  return element;
}

function shadow(element: CraftTabs, selector: string): HTMLElement | null {
  return element.shadowRoot?.querySelector(selector) ?? null;
}

/** The component's rules, keyed by selector, for structural CSS checks. */
function rules(): Map<string, string> {
  const map = new Map<string, string>();
  const cssText = styles.cssText.replace(/\/\*[\s\S]*?\*\//g, '');

  for (const [, selector, body] of cssText.matchAll(
    /([^{}]+)\{([^{}]*)\}/g
  ) as Iterable<RegExpMatchArray>) {
    map.set(selector!.trim().replace(/\s+/g, ' '), body!.trim());
  }

  return map;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('structure', () => {
  it('renders the tablist and panel regions as parts', async () => {
    const element = await createTabs();

    expect(shadow(element, '[part="base"]')).not.toBeNull();

    const tabGroup = shadow(element, '[part="tab-group"]')!;
    expect(tabGroup.getAttribute('role')).toBe('tablist');

    expect(shadow(element, '[part="panels"]')).not.toBeNull();
  });

  it('keeps the class names and slots Lion depends on', async () => {
    const element = await createTabs();

    // LionTabs styles against these classes and queries its own shadow root
    // for `slot[name=tab]` to observe slot changes — renaming either breaks
    // selection silently.
    const tabSlot = shadow(element, '.tabs__tab-group slot[name="tab"]');
    const panelSlot = shadow(element, '.tabs__panels slot[name="panel"]');

    expect(tabSlot).not.toBeNull();
    expect(panelSlot).not.toBeNull();
    expect((tabSlot as HTMLSlotElement).assignedNodes().length).toBe(3);
    expect((panelSlot as HTMLSlotElement).assignedNodes().length).toBe(3);
  });
});

describe('layout', () => {
  it('defaults to horizontal and reflects the attribute', async () => {
    const element = await createTabs();

    expect(element.layout).toBe('horizontal');
    expect(element.getAttribute('layout')).toBe('horizontal');
  });

  it('marks the tablist orientation', async () => {
    const element = await createTabs({attrs: {layout: 'vertical'}});

    expect(element.layout).toBe('vertical');
    expect(
      shadow(element, '[part="tab-group"]')!.getAttribute('aria-orientation')
    ).toBe('vertical');
  });

  it('hands the vertical indicator geometry down to the tabs', async () => {
    // The tabs can't see the strip's layout, so the vertical variant has to
    // publish the indicator vars they inherit. Asserted against the stylesheet
    // because there's no cascade in this environment.
    const vertical = rules().get(":host([layout='vertical'])");

    expect(vertical).toBeDefined();
    expect(vertical).toContain('--c-tab-indicator-inset-inline-end: -1px');
    expect(vertical).toContain('--c-tab-indicator-inset-block-end: 0');
  });
});

describe('craft-tab', () => {
  it('reflects disabled', async () => {
    const element = await createTabs();
    const tab = element.querySelector('craft-tab') as CraftTab;

    expect(tab.disabled).toBe(false);

    tab.disabled = true;
    await tab.updateComplete;
    expect(tab.hasAttribute('disabled')).toBe(true);
  });

  it('reads selection off the attribute the strip writes', async () => {
    const element = await createTabs();
    const tab = element.querySelector('craft-tab') as CraftTab;

    expect(tab.selected).toBe(false);

    // Stand in for LionTabs, which sets this directly rather than via a
    // property.
    tab.setAttribute('selected', 'true');
    expect(tab.selected).toBe(true);
  });
});
