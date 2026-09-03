import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftTab from '../tab/tab.js';
import type CraftTabs from './tabs.js';
import {tabsPlacements} from './tabs.js';
import styles from './tabs.styles.js';
import '../tab/tab.js';
import './tabs.js';

/*
 * SCOPE: this covers what `CraftTabs` adds on top of `LionTabs` — the wrapper
 * and parts, `placement`, `collapsible`, the tab's own state, and
 * external-panel mode (which this component implements itself, so it is fully
 * covered here).
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

/**
 * The declarations of every rule whose selector mentions `fragment`, joined —
 * a placement's styling is spread over a handful of rules, some of them shared
 * between the two placements on an axis.
 */
function declarations(fragment: string): string {
  return [...rules()]
    .filter(([selector]) => selector.includes(fragment))
    .map(([, body]) => body)
    .join('\n');
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

describe('placement', () => {
  it('defaults to block-start and reflects the attribute', async () => {
    const element = await createTabs();

    expect(element.placement).toBe('block-start');
    expect(element.getAttribute('placement')).toBe('block-start');
  });

  it('reflects every placement', async () => {
    const element = await createTabs();

    for (const placement of tabsPlacements) {
      element.placement = placement;
      await element.updateComplete;

      expect(element.getAttribute('placement')).toBe(placement);
    }
  });

  it('derives the tablist orientation from the axis', async () => {
    const element = await createTabs();
    const orientation = () =>
      shadow(element, '[part="tab-group"]')!.getAttribute('aria-orientation');

    for (const [placement, expected] of [
      ['block-start', 'horizontal'],
      ['block-end', 'horizontal'],
      ['inline-start', 'vertical'],
      ['inline-end', 'vertical'],
    ] as const) {
      element.placement = placement;
      await element.updateComplete;

      expect(orientation()).toBe(expected);
    }
  });

  it('hands the indicator geometry down to the tabs', () => {
    // The tabs can't see the strip's placement, so every placement that isn't
    // <craft-tab>'s own default has to publish the indicator vars they inherit.
    // Asserted against the stylesheet because there's no cascade in this
    // environment.
    const inlineStart = declarations("[placement='inline-start']");

    expect(inlineStart).toContain('--c-tab-indicator-inset-inline-end: -1px');
    expect(inlineStart).toContain('--c-tab-indicator-inset-block-end: 0');
    expect(inlineStart).toContain(
      '--c-tab-indicator-inline-size: calc(2rem / 16)'
    );

    const inlineEnd = declarations("[placement='inline-end']");

    expect(inlineEnd).toContain('--c-tab-indicator-inset-inline-start: -1px');
    expect(inlineEnd).toContain('--c-tab-indicator-inset-block-end: 0');

    // A strip below the panels flips the indicator to the tab's block start.
    expect(declarations("[placement='block-end']")).toContain(
      '--c-tab-indicator-inset-block-start: -1px'
    );
  });

  it('moves the rule to the edge facing the panels', () => {
    expect(rules().get('.tabs__strip')).toContain(
      'border-block-end-width: 1px'
    );
    expect(declarations("[placement='block-end']")).toContain(
      'border-block-start-width: 1px'
    );
    expect(declarations("[placement='inline-start']")).toContain(
      'border-inline-end-width: 1px'
    );
    expect(declarations("[placement='inline-end']")).toContain(
      'border-inline-start-width: 1px'
    );
  });

  it('is written logically, so RTL needs no rules of its own', () => {
    // The strip is placed by flex direction and the rule and indicator by
    // logical properties, which is what makes inline-start land on the right
    // in RTL for free. The RightToLeft story checks that it actually does, in
    // an environment with a layout engine.
    const css = styles.cssText.replace(/\/\*[\s\S]*?\*\//g, '');

    expect(css).not.toMatch(/\b(left|right)\s*:/);
    expect(css).not.toMatch(/border-(left|right|top|bottom)/);
    expect(css).not.toMatch(/\[dir=/);
  });
});

describe('layout (deprecated)', () => {
  it('maps the axis onto a placement', async () => {
    const element = await createTabs({attrs: {layout: 'vertical'}});

    expect(element.placement).toBe('inline-start');
    expect(element.getAttribute('placement')).toBe('inline-start');
    expect(
      shadow(element, '[part="tab-group"]')!.getAttribute('aria-orientation')
    ).toBe('vertical');

    element.layout = 'horizontal';
    await element.updateComplete;

    expect(element.placement).toBe('block-start');
  });

  it('reports the axis of whatever placement is set', async () => {
    const element = await createTabs();

    expect(element.layout).toBe('horizontal');
    // Still reflected, so an existing consumer keying off the attribute reads
    // the same thing it always did.
    expect(element.getAttribute('layout')).toBe('horizontal');

    element.placement = 'inline-end';
    await element.updateComplete;

    expect(element.layout).toBe('vertical');

    element.placement = 'block-end';
    await element.updateComplete;

    expect(element.layout).toBe('horizontal');
  });
});

describe('size', () => {
  it('defaults to medium and reflects the attribute', async () => {
    const element = await createTabs();

    expect(element.size).toBe('medium');
    expect(element.getAttribute('size')).toBe('medium');

    element.size = 'small';
    await element.updateComplete;
    expect(element.getAttribute('size')).toBe('small');
  });

  it('scales the strip by font size alone', async () => {
    // The tabs are slotted, so they inherit the strip's font size through the
    // flattened tree; their padding is em-based and follows. Asserted against
    // the stylesheet because there's no cascade in this environment.
    const map = rules();

    expect(map.get('.tabs__strip')).toContain(
      'font-size: var(--c-tabs-font-size, var(--c-text-base))'
    );
    expect(map.get(":host([size='small'])")).toContain(
      '--c-tabs-font-size: var(--c-text-sm)'
    );
    expect(map.get(":host([size='large'])")).toContain(
      '--c-tabs-font-size: var(--c-text-lg)'
    );

    // Medium is the bare fallback above, so there is no per-tab padding rule
    // to keep in sync with it.
    expect(map.has(":host([size='medium'])")).toBe(false);
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
  attrs = {} as Record<string, string>,
} = {}): Promise<{
  element: CraftTabs;
  tabs: CraftTab[];
  sections: HTMLElement[];
}> {
  const element = document.createElement('craft-tabs') as CraftTabs;
  const sections: HTMLElement[] = [];

  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }

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

  it('stays quiet on the initial render', async () => {
    let fired = 0;
    const element = document.createElement('craft-tabs');
    element.addEventListener('craft-tab-show', () => {
      fired++;
    });
    document.body.append(element);
    await element.updateComplete;

    // Selecting the first tab on load is not a change anyone asked for.
    expect(fired).toBe(0);
  });

  it('switches panels on click and fires craft-tab-show', async () => {
    const {element, tabs, sections} = await createExternalTabs();
    let fired = 0;
    element.addEventListener('craft-tab-show', () => {
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

/*
 * Collapsing is covered here in external-panel mode, where this component owns
 * the selection outright. The slotted half of it — Lion's own store, which
 * skips a selection it can't find in it — needs a `slotchange` happy-dom never
 * fires, so it's covered by the IconToolbar story.
 */
describe('collapsible', () => {
  /** Escape, as it arrives from a focused tab. */
  function escape(tab: CraftTab) {
    tab.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Escape', bubbles: true})
    );
  }

  it('collapses the panel region to nothing when nothing is selected', async () => {
    const element = await createTabs({attrs: {'selected-index': '-1'}});
    const panels = shadow(element, '[part="panels"]')!;

    expect(element.selectedIndex).toBe(-1);
    expect(panels.hidden).toBe(true);
    // Hidden rather than emptied: dropping the slot would unassign the panels.
    expect(shadow(element, '.tabs__panels slot[name="panel"]')).not.toBeNull();
    // And [hidden] has to be spelled out, since LionTabs gives the region an
    // author `display: block` that beats the UA rule.
    expect(rules().get('.tabs__panels[hidden]')).toContain('display: none');

    element.selectedIndex = 0;
    await element.updateComplete;

    expect(panels.hidden).toBe(false);
  });

  /**
   * A surrounding layout gives the panel's grid track its space back with
   * `:has(craft-tabs[collapsed])`, so the attribute is API, not bookkeeping.
   */
  it('reflects a collapsed attribute for a surrounding layout to select on', async () => {
    const {element, tabs} = await createExternalTabs({
      attrs: {collapsible: ''},
    });

    expect(element.hasAttribute('collapsed')).toBe(false);

    tabs[0]!.click();
    await element.updateComplete;

    expect(element.selectedIndex).toBe(-1);
    expect(element.hasAttribute('collapsed')).toBe(true);

    tabs[1]!.click();
    await element.updateComplete;

    expect(element.hasAttribute('collapsed')).toBe(false);
  });

  it('deselects the selected tab when it is clicked again', async () => {
    const {element, tabs, sections} = await createExternalTabs({
      attrs: {collapsible: ''},
    });

    let reported: number | undefined;
    element.addEventListener('craft-tab-show', (event) => {
      reported = (event.target as CraftTabs).selectedIndex;
    });

    tabs[0]!.click();
    await element.updateComplete;

    expect(element.selectedIndex).toBe(-1);
    expect(reported).toBe(-1);
    expect(sections.every((s) => s.classList.contains('hidden'))).toBe(true);
    expect(tabs.map((tab) => tab.getAttribute('aria-selected'))).toEqual([
      'false',
      'false',
      'false',
    ]);
  });

  it('keeps exactly one tab in the tab order while collapsed', async () => {
    const {element, tabs} = await createExternalTabs({
      attrs: {collapsible: ''},
    });

    tabs[2]!.click();
    await element.updateComplete;
    tabs[2]!.click();
    await element.updateComplete;

    expect(element.selectedIndex).toBe(-1);
    // The tab the selection was last on, so the strip doesn't snap the tab
    // order back to the front while it's closed.
    expect(tabs.map((tab) => tab.getAttribute('tabindex'))).toEqual([
      '-1',
      '-1',
      '0',
    ]);
  });

  it('starts collapsed from selected-index="-1"', async () => {
    const {element, tabs, sections} = await createExternalTabs({
      attrs: {collapsible: '', 'selected-index': '-1'},
    });

    expect(element.selectedIndex).toBe(-1);
    expect(sections.every((s) => s.classList.contains('hidden'))).toBe(true);
    expect(tabs.map((tab) => tab.getAttribute('tabindex'))).toEqual([
      '0',
      '-1',
      '-1',
    ]);
  });

  it('reopens on the next click, wherever it lands', async () => {
    const {element, tabs, sections} = await createExternalTabs({
      attrs: {collapsible: '', 'selected-index': '-1'},
    });

    tabs[1]!.click();
    await element.updateComplete;

    expect(element.selectedIndex).toBe(1);
    expect(sections[1]!.classList.contains('hidden')).toBe(false);
    expect(tabs.map((tab) => tab.getAttribute('tabindex'))).toEqual([
      '-1',
      '0',
      '-1',
    ]);

    // A different tab still just selects, rather than toggling anything.
    tabs[2]!.click();
    await element.updateComplete;

    expect(element.selectedIndex).toBe(2);
  });

  it('closes on Escape from a tab', async () => {
    const {element, tabs} = await createExternalTabs({
      attrs: {collapsible: ''},
    });

    escape(tabs[0]!);
    await element.updateComplete;

    expect(element.selectedIndex).toBe(-1);
  });

  it('opens at the end you arrow out of', async () => {
    const {element, tabs} = await createExternalTabs({
      attrs: {collapsible: '', 'selected-index': '-1'},
    });

    arrow(tabs[0]!, 'ArrowRight');
    await element.updateComplete;

    expect(element.selectedIndex).toBe(0);

    tabs[0]!.click();
    await element.updateComplete;

    expect(element.selectedIndex).toBe(-1);

    arrow(tabs[0]!, 'ArrowLeft');
    await element.updateComplete;

    expect(element.selectedIndex).toBe(2);
  });

  it('leaves a strip that isn’t collapsible alone', async () => {
    const {element, tabs, sections} = await createExternalTabs();
    let fired = 0;
    element.addEventListener('craft-tab-show', () => {
      fired++;
    });

    tabs[0]!.click();
    escape(tabs[0]!);
    await element.updateComplete;

    expect(element.selectedIndex).toBe(0);
    expect(fired).toBe(0);
    expect(sections[0]!.classList.contains('hidden')).toBe(false);
    expect(shadow(element, '[part="panels"]')!.hidden).toBe(false);
  });
});
