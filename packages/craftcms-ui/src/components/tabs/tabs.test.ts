import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftTab from '../tab/tab.js';
import type CraftTabs from './tabs.js';
import styles from './tabs.styles.js';
import '../tab/tab.js';
import './tabs.js';

/*
 * SCOPE: this covers what `CraftTabs` adds on top of `LionTabs` — the wrapper
 * and parts, the `layout` axis, the tab's own state, and external-panel mode
 * (which this component implements itself, so it is fully covered here).
 *
 * Lion's *slotted* selection machinery is NOT exercised here, and can't be: it
 * bootstraps from a `slotchange` on the shadow tab slot, and happy-dom never
 * fires one (nodes are assigned — `assignedNodes()` is correct — but the event
 * isn't dispatched). Nothing gets wired, so every selection assertion would
 * fail against a component that works fine in a browser. Slotted selection,
 * keyboard navigation, and disabled-tab skipping are covered by the play
 * functions in tabs.stories.ts, which run in real Chromium.
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

  it('keeps the overflow menu out of the tablist, and hidden until needed', async () => {
    const element = await createTabs();
    const menu = shadow(element, '[part="overflow-menu"]')!;

    expect(menu).not.toBeNull();
    // A tablist may only contain tabs, so the menu is its sibling.
    expect(menu.closest('[role="tablist"]')).toBeNull();
    expect(shadow(element, '[part="strip"]')!.contains(menu)).toBe(true);
    // Nothing overflows in this environment (no layout engine), so it stays
    // out of the way rather than rendering an empty menu.
    expect((menu as HTMLElement).hidden).toBe(true);
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

/**
 * Builds a strip whose panels live outside it, the shape a server-rendered
 * field layout produces: the tab bar in one part of the page, the panels
 * (hidden by the server past the first) in another.
 */
async function createExternalTabs({
  count = 3,
  disabled = [] as number[],
  panels = true,
} = {}): Promise<{
  element: CraftTabs;
  tabs: CraftTab[];
  sections: HTMLElement[];
}> {
  const element = document.createElement('craft-tabs') as CraftTabs;
  const sections: HTMLElement[] = [];

  for (let i = 0; i < count; i++) {
    const tab = document.createElement('craft-tab') as CraftTab;
    tab.slot = 'tab';
    tab.setAttribute('controls', `panel-${i}`);
    tab.textContent = `Tab ${i}`;
    if (disabled.includes(i)) {
      tab.setAttribute('disabled', '');
    }
    element.append(tab);

    if (panels) {
      const section = document.createElement('section');
      section.id = `panel-${i}`;
      if (i > 0) {
        section.classList.add('hidden');
      }
      sections.push(section);
    }
  }

  document.body.append(element, ...sections);
  await element.updateComplete;

  return {
    element,
    tabs: Array.from(element.querySelectorAll('craft-tab')),
    sections,
  };
}

function arrow(tab: CraftTab, key: string) {
  tab.dispatchEvent(new KeyboardEvent('keydown', {key, bubbles: true}));
  tab.dispatchEvent(new KeyboardEvent('keyup', {key, bubbles: true}));
}

describe('external-panel mode', () => {
  it('wires the tab/tabpanel contract across the two halves', async () => {
    const {tabs, sections} = await createExternalTabs();

    tabs.forEach((tab, index) => {
      const section = sections[index]!;

      expect(tab.getAttribute('role')).toBe('tab');
      expect(tab.getAttribute('aria-controls')).toBe(section.id);
      expect(section.getAttribute('role')).toBe('tabpanel');
      expect(section.getAttribute('aria-labelledby')).toBe(tab.id);
      expect(tab.id).not.toBe('');
      // Panels not starting with something focusable have to be reachable.
      expect(section.getAttribute('tabindex')).toBe('0');
    });
  });

  it('shows only the selected panel, using the server’s hidden class', async () => {
    const {sections} = await createExternalTabs();

    expect(sections[0]!.classList.contains('hidden')).toBe(false);
    expect(sections[1]!.classList.contains('hidden')).toBe(true);
    expect(sections[2]!.classList.contains('hidden')).toBe(true);
  });

  it('leaves the panels’ ids alone', async () => {
    // Lion's own pairing renames the panels it sets up; these ids come from
    // the server and are what `controls` (and other code) references.
    const {sections} = await createExternalTabs();

    expect(sections.map((section) => section.id)).toEqual([
      'panel-0',
      'panel-1',
      'panel-2',
    ]);
  });

  it('switches panels on click and fires selected-changed', async () => {
    const {element, tabs, sections} = await createExternalTabs();
    let fired = 0;
    element.addEventListener('selected-changed', () => {
      fired++;
    });

    tabs[2]!.click();
    await element.updateComplete;

    expect(fired).toBe(1);
    expect(element.selectedIndex).toBe(2);
    expect(sections[0]!.classList.contains('hidden')).toBe(true);
    expect(sections[2]!.classList.contains('hidden')).toBe(false);
    expect(tabs[2]!.getAttribute('aria-selected')).toBe('true');
    expect(tabs[0]!.getAttribute('aria-selected')).toBe('false');
  });

  it('keeps a roving tabindex', async () => {
    const {element, tabs} = await createExternalTabs();

    expect(tabs[0]!.getAttribute('tabindex')).toBe('0');
    expect(tabs[1]!.getAttribute('tabindex')).toBe('-1');

    tabs[1]!.click();
    await element.updateComplete;

    expect(tabs[0]!.getAttribute('tabindex')).toBe('-1');
    expect(tabs[1]!.getAttribute('tabindex')).toBe('0');
  });

  it('navigates with the arrow keys, wrapping at both ends', async () => {
    const {element, tabs} = await createExternalTabs();

    arrow(tabs[0]!, 'ArrowRight');
    await element.updateComplete;
    expect(element.selectedIndex).toBe(1);

    arrow(tabs[1]!, 'ArrowLeft');
    await element.updateComplete;
    expect(element.selectedIndex).toBe(0);

    // Wraps backwards off the first tab...
    arrow(tabs[0]!, 'ArrowLeft');
    await element.updateComplete;
    expect(element.selectedIndex).toBe(2);

    // ...and forwards off the last.
    arrow(tabs[2]!, 'ArrowRight');
    await element.updateComplete;
    expect(element.selectedIndex).toBe(0);
  });

  it('jumps to the ends with Home and End', async () => {
    const {element, tabs} = await createExternalTabs();

    arrow(tabs[0]!, 'End');
    await element.updateComplete;
    expect(element.selectedIndex).toBe(2);

    arrow(tabs[2]!, 'Home');
    await element.updateComplete;
    expect(element.selectedIndex).toBe(0);
  });

  it('skips disabled tabs when navigating, and refuses to select one', async () => {
    const {element, tabs} = await createExternalTabs({disabled: [1]});

    arrow(tabs[0]!, 'ArrowRight');
    await element.updateComplete;
    expect(element.selectedIndex).toBe(2);

    tabs[1]!.click();
    await element.updateComplete;
    expect(element.selectedIndex).toBe(2);
  });

  it('moves the initial selection off a disabled first tab', async () => {
    const {element} = await createExternalTabs({disabled: [0]});

    expect(element.selectedIndex).toBe(1);
  });

  it('honors selected-index set from outside', async () => {
    const {element, sections} = await createExternalTabs();

    element.setAttribute('selected-index', '2');
    await element.updateComplete;

    expect(sections[2]!.classList.contains('hidden')).toBe(false);
    expect(sections[0]!.classList.contains('hidden')).toBe(true);
  });

  it('rewires replaced panels on refresh()', async () => {
    const {element, tabs, sections} = await createExternalTabs();

    // Stand in for a re-rendered fragment: same ids, new elements, and back to
    // the server's initial visibility.
    const replacements = sections.map((section, index) => {
      const fresh = document.createElement('section');
      fresh.id = section.id;
      if (index > 0) {
        fresh.classList.add('hidden');
      }
      section.replaceWith(fresh);
      return fresh;
    });

    tabs[2]!.click();
    await element.updateComplete;
    element.refresh();

    expect(replacements[2]!.getAttribute('role')).toBe('tabpanel');
    expect(replacements[2]!.getAttribute('aria-labelledby')).toBe(tabs[2]!.id);
    expect(replacements[2]!.classList.contains('hidden')).toBe(false);
    expect(replacements[0]!.classList.contains('hidden')).toBe(true);
  });

  it('stays quiet while no panels have been rendered yet', async () => {
    // The panel markup is often injected after the strip mounts; a strip that
    // resolves nothing is waiting, not misconfigured.
    const error = vi.spyOn(console, 'error').mockImplementation(() => {});

    await createExternalTabs({panels: false});

    expect(error).not.toHaveBeenCalled();
    error.mockRestore();
  });

  it('reports a tab whose panel is missing once the others resolve', async () => {
    const error = vi.spyOn(console, 'error').mockImplementation(() => {});

    const {element} = await createExternalTabs();
    document.getElementById('panel-1')!.remove();
    element.refresh();

    expect(error).toHaveBeenCalledOnce();
    expect(error.mock.calls[0]![0]).toContain('panel-1');
    error.mockRestore();
  });

  it('arrows past collapsed tabs', async () => {
    // Overflow measurement needs a layout engine, so this stands in for it by
    // collapsing the middle tab the way #applyOverflow would.
    const {element, tabs} = await createExternalTabs();
    tabs[1]!.toggleAttribute('hidden', true);

    arrow(tabs[0]!, 'ArrowRight');
    await element.updateComplete;

    expect(element.selectedIndex).toBe(2);
  });

  it('drops its listeners when disconnected', async () => {
    const {element, tabs} = await createExternalTabs();

    element.remove();
    tabs[2]!.click();
    await element.updateComplete;

    expect(element.selectedIndex).toBe(0);
  });
});
