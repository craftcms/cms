import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {DisclosureMenu} from '../src/disclosure-menu';
import {UiLayerManager} from '../src/managers/ui-layer-manager';
import {setUiLayerManager} from '../src/managers/registry';
import {DOWN_KEY, ESC_KEY, RETURN_KEY, TAB_KEY, UP_KEY} from '../src/constants';
import {globals} from '../src/globals';

// happy-dom notes:
// - No layout: `getBoundingClientRect()` / `offsetWidth` / `getClientRects()`
//   are all 0/empty. Focusable elements get `getClientRects` stubbed to look
//   visible (`makeVisible`), and the positioning test stubs `getBoundingClientRect`
//   + `offsetWidth/Height` (`mockRect`).
// - No `element.animate`: the hide fade finalizes synchronously, so hide() is
//   observable immediately (no RAF/animation mock needed — DisclosureMenu uses
//   neither).

let manager: UiLayerManager;
let idSeq = 0;

/** Stub an element's layout boxes so the focusable matcher treats it as visible. */
function makeVisible(el: Element): void {
  vi.spyOn(el, 'getClientRects').mockReturnValue([
    {width: 10, height: 10},
  ] as unknown as DOMRectList);
}

/** Stub an element's bounding rect + offset box (positioning math). */
function mockRect(
  el: Element,
  top: number,
  left: number,
  width = 0,
  height = 0
): void {
  vi.spyOn(el, 'getBoundingClientRect').mockReturnValue({
    top,
    left,
    right: left + width,
    bottom: top + height,
    width,
    height,
    x: left,
    y: top,
    toJSON: () => ({}),
  } as DOMRect);
  Object.defineProperty(el, 'offsetWidth', {value: width, configurable: true});
  Object.defineProperty(el, 'offsetHeight', {
    value: height,
    configurable: true,
  });
}

interface BuiltMenu {
  trigger: HTMLButtonElement;
  container: HTMLDivElement;
}

/**
 * Build a trigger + an `aria-controls`-linked container with `itemLabels` items.
 * Both the trigger and the item buttons are stubbed visible.
 */
function buildMenu(
  itemLabels: string[] = [],
  opts: {nextSibling?: boolean} = {}
): BuiltMenu {
  const id = `menu-${++idSeq}`;

  const trigger = document.createElement('button');
  trigger.type = 'button';
  trigger.textContent = 'Open';
  trigger.setAttribute('aria-controls', id);
  makeVisible(trigger);
  document.body.appendChild(trigger);

  const container = document.createElement('div');
  container.id = id;
  container.className = 'menu menu--disclosure';
  const ul = document.createElement('ul');
  for (const labelText of itemLabels) {
    const li = document.createElement('li');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'menu-item';
    const label = document.createElement('span');
    label.className = 'menu-item-label';
    label.textContent = labelText;
    btn.appendChild(label);
    li.appendChild(btn);
    ul.appendChild(li);
    makeVisible(btn);
  }
  container.appendChild(ul);

  if (opts.nextSibling) {
    trigger.after(container);
  } else {
    document.body.appendChild(container);
  }

  return {trigger, container};
}

beforeEach(() => {
  manager = new UiLayerManager();
  setUiLayerManager(manager);
  DisclosureMenu.instances = [];
  idSeq = 0;
  document.body.innerHTML = '';
  document.body.className = '';
  globals.scrollContainer = window;
  globals.rtl = false;
});

afterEach(() => {
  vi.restoreAllMocks();
  vi.useRealTimers();
});

describe('DisclosureMenu construction', () => {
  it('applies defaults merged with passed settings', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger, {windowSpacing: 12});
    expect(menu.settings!.windowSpacing).toBe(12);
    expect(menu.settings!.position).toBeNull();
    expect(menu.settings!.withSearchInput).toBe(false);
  });

  it('uses the defaults when no settings are passed', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    expect(menu.settings!.windowSpacing).toBe(5);
  });

  it('resolves the container via aria-controls and flags the trigger', () => {
    const {trigger, container} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    expect(menu.$container).toBe(container);
    expect(trigger.getAttribute('data-disclosure-trigger')).toBe('true');
  });

  it('resolves a container that sits as the trigger’s next sibling', () => {
    // The container immediately follows the trigger and carries the
    // aria-controls id (the next-sibling resolution path).
    const id = 'sibling-menu';
    const trigger = document.createElement('button');
    trigger.setAttribute('aria-controls', id);
    document.body.appendChild(trigger);
    const container = document.createElement('div');
    container.id = id;
    trigger.after(container);
    const menu = new DisclosureMenu(trigger);
    expect(menu.$container).toBe(container);
  });

  it('throws when no disclosure container can be found', () => {
    const trigger = document.createElement('button');
    trigger.setAttribute('aria-controls', 'does-not-exist');
    document.body.appendChild(trigger);
    expect(() => new DisclosureMenu(trigger)).toThrow();
  });

  it('defaults aria-expanded to false when absent', () => {
    const {trigger} = buildMenu();
    new DisclosureMenu(trigger);
    expect(trigger.getAttribute('aria-expanded')).toBe('false');
  });

  it('preserves an existing aria-expanded value', () => {
    const {trigger} = buildMenu();
    trigger.setAttribute('aria-expanded', 'true');
    new DisclosureMenu(trigger);
    expect(trigger.getAttribute('aria-expanded')).toBe('true');
  });

  it('registers itself in instances and via getInstance (trigger + container)', () => {
    const {trigger, container} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    expect(DisclosureMenu.instances).toContain(menu);
    expect(DisclosureMenu.getInstance(trigger)).toBe(menu);
    expect(DisclosureMenu.getInstance(container)).toBe(menu);
  });

  it('moves the container to the end of <body>', () => {
    const {container} = buildMenu();
    const trigger2 = buildMenu().trigger; // some trailing element
    void trigger2;
    new DisclosureMenu(document.querySelector('button')!);
    expect(document.body.lastElementChild).toBe(container);
  });

  it('warns and bails on double-instantiation', () => {
    const {trigger} = buildMenu();
    new DisclosureMenu(trigger);
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
    const second = new DisclosureMenu(trigger);
    expect(warn).toHaveBeenCalledOnce();
    // The second instance bailed before wiring its container.
    expect(second.$container).toBeNull();
  });

  it('coerces a selector-string trigger', () => {
    const {trigger} = buildMenu();
    trigger.id = 'string-trigger';
    const menu = new DisclosureMenu('#string-trigger');
    expect(menu.$trigger).toBe(trigger);
  });
});

describe('DisclosureMenu show / hide', () => {
  it('show() expands, fires beforeShow + show, sets aria-expanded', () => {
    const {trigger} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger);
    const beforeShow = vi.fn();
    const onShow = vi.fn();
    menu.on('beforeShow', beforeShow);
    menu.on('show', onShow);

    menu.show();

    expect(menu.isExpanded()).toBe(true);
    expect(trigger.getAttribute('aria-expanded')).toBe('true');
    expect(menu.$container!.classList.contains('visible')).toBe(true);
    expect(beforeShow).toHaveBeenCalledOnce();
    expect(onShow).toHaveBeenCalledOnce();
  });

  it('hide() collapses, fires hide, clears the visible class', () => {
    const {trigger} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger);
    const onHide = vi.fn();
    menu.on('hide', onHide);

    menu.show();
    menu.hide();

    expect(menu.isExpanded()).toBe(false);
    expect(trigger.getAttribute('aria-expanded')).toBe('false');
    expect(menu.$container!.classList.contains('visible')).toBe(false);
    expect(onHide).toHaveBeenCalledOnce();
  });

  it('show() is a no-op when already expanded', () => {
    const {trigger} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger);
    menu.show();
    const onShow = vi.fn();
    menu.on('show', onShow);
    menu.show();
    expect(onShow).not.toHaveBeenCalled();
  });

  it('hide() is a no-op when not expanded', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    const onHide = vi.fn();
    menu.on('hide', onHide);
    menu.hide();
    expect(onHide).not.toHaveBeenCalled();
  });

  it('show() does nothing when the trigger is disabled', () => {
    const {trigger} = buildMenu(['Alpha']);
    trigger.classList.add('disabled');
    const menu = new DisclosureMenu(trigger);
    menu.show();
    expect(menu.isExpanded()).toBe(false);
  });

  it('handleTriggerClick toggles open/closed', () => {
    const {trigger} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger);
    menu.handleTriggerClick();
    expect(menu.isExpanded()).toBe(true);
    menu.handleTriggerClick();
    expect(menu.isExpanded()).toBe(false);
  });
});

describe('DisclosureMenu layer + Escape', () => {
  it('adds a UI layer on show and removes it on hide', () => {
    const {trigger} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger);
    expect(manager.layer).toBe(0);
    menu.show();
    expect(manager.layer).toBe(1);
    menu.hide();
    expect(manager.layer).toBe(0);
  });

  it('closes on Escape via the registered shortcut', () => {
    const {trigger} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger);
    menu.show();
    const ev = new KeyboardEvent('keydown', {keyCode: ESC_KEY} as never);
    manager.triggerShortcut(ev);
    expect(menu.isExpanded()).toBe(false);
  });
});

describe('DisclosureMenu focus management', () => {
  it('focuses the first item on show', () => {
    const {trigger} = buildMenu(['Alpha', 'Beta']);
    const menu = new DisclosureMenu(trigger);
    menu.show();
    const first = menu.$container!.querySelector('button')!;
    expect(document.activeElement).toBe(first);
  });

  it('falls back to focusing the container when there are no items', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    menu.show();
    expect(menu.$container!.getAttribute('tabindex')).toBe('-1');
    expect(document.activeElement).toBe(menu.$container);
  });

  it('restores focus to the trigger on hide when focus was inside', () => {
    const {trigger} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger);
    menu.show();
    const item = menu.$container!.querySelector('button')!;
    item.focus();
    expect(document.activeElement).toBe(item);
    menu.hide();
    expect(document.activeElement).toBe(trigger);
  });
});

describe('DisclosureMenu keyboard navigation', () => {
  it('Arrow Down / Up move focus between items', () => {
    const {trigger, container} = buildMenu(['Alpha', 'Beta', 'Gamma']);
    const menu = new DisclosureMenu(trigger);
    menu.show();
    const [a, b] = Array.from(container.querySelectorAll('button'));
    a!.focus();

    container.dispatchEvent(
      new KeyboardEvent('keydown', {keyCode: DOWN_KEY, bubbles: true} as never)
    );
    expect(document.activeElement).toBe(b);

    container.dispatchEvent(
      new KeyboardEvent('keydown', {keyCode: UP_KEY, bubbles: true} as never)
    );
    expect(document.activeElement).toBe(a);
  });

  it('Shift+Tab on the first item returns focus to the trigger', () => {
    const {trigger, container} = buildMenu(['Alpha', 'Beta']);
    const menu = new DisclosureMenu(trigger);
    menu.show();
    const first = container.querySelector('button')!;
    first.focus();
    const ev = new KeyboardEvent('keydown', {
      keyCode: TAB_KEY,
      shiftKey: true,
      bubbles: true,
    } as never);
    Object.defineProperty(ev, 'target', {value: first});
    container.dispatchEvent(ev);
    expect(document.activeElement).toBe(trigger);
  });

  it('Tab on the trigger while expanded moves focus into the menu', () => {
    const {trigger} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger);
    menu.show();
    trigger.focus();
    const ev = new KeyboardEvent('keydown', {keyCode: TAB_KEY} as never);
    trigger.dispatchEvent(ev);
    const first = menu.$container!.querySelector('button')!;
    expect(document.activeElement).toBe(first);
  });
});

describe('DisclosureMenu type-ahead search', () => {
  it('focuses the first item whose text starts with the typed string', () => {
    const {trigger, container} = buildMenu(['Apple', 'Banana', 'Cherry']);
    const menu = new DisclosureMenu(trigger);
    menu.show();

    container.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'b', bubbles: true} as never)
    );

    const banana = Array.from(container.querySelectorAll('button')).find((b) =>
      b.textContent!.includes('Banana')
    );
    expect(document.activeElement).toBe(banana);
  });

  it('accumulates the search buffer across keystrokes and clears it', () => {
    const {trigger, container} = buildMenu(['Car', 'Cat', 'Dog']);
    const menu = new DisclosureMenu(trigger);
    menu.show();

    container.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'c', bubbles: true} as never)
    );
    container.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'a', bubbles: true} as never)
    );
    container.dispatchEvent(
      new KeyboardEvent('keydown', {key: 't', bubbles: true} as never)
    );
    expect(menu.searchStr).toBe('cat');
    const cat = Array.from(container.querySelectorAll('button')).find((b) =>
      b.textContent!.includes('Cat')
    );
    expect(document.activeElement).toBe(cat);

    menu.clearSearchStr();
    expect(menu.searchStr).toBe('');
  });
});

describe('DisclosureMenu outside-click dismissal', () => {
  it('hides when a mousedown lands outside the menu', () => {
    const {trigger} = buildMenu(['Alpha']);
    const outside = document.createElement('div');
    document.body.appendChild(outside);
    const menu = new DisclosureMenu(trigger);
    menu.show();

    outside.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));
    expect(menu.isExpanded()).toBe(false);
  });

  it('does not hide when the mousedown is inside the menu', () => {
    const {trigger, container} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger);
    menu.show();

    const item = container.querySelector('button')!;
    item.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));
    expect(menu.isExpanded()).toBe(true);
  });
});

describe('DisclosureMenu item building', () => {
  it('addItem builds a button item and returns its element', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    const el = menu.addItem({label: 'Rename'});
    expect(el.tagName).toBe('BUTTON');
    expect(el.classList.contains('menu-item')).toBe(true);
    expect(el.querySelector('.menu-item-label')!.textContent).toBe('Rename');
  });

  it('infers a link item from a url', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    const el = menu.addItem({label: 'Edit', url: '/edit'});
    expect(el.tagName).toBe('A');
    expect((el as HTMLAnchorElement).getAttribute('href')).toBe('/edit');
  });

  it('applies selected / destructive / disabled flags', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    const el = menu.addItem({
      label: 'Delete',
      selected: true,
      destructive: true,
      disabled: true,
    });
    expect(el.classList.contains('sel')).toBe(true);
    expect(el.classList.contains('error')).toBe(true);
    expect(el.getAttribute('data-destructive')).toBe('true');
    expect(el.classList.contains('disabled')).toBe(true);
  });

  it('sets the formsubmit class + data-action for action items', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    const el = menu.addItem({label: 'Save', action: 'entries/save'});
    expect(el.classList.contains('formsubmit')).toBe(true);
    expect(el.getAttribute('data-action')).toBe('entries/save');
  });

  it('throws on an unsupported item configuration', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    expect(() => menu.createItem(42 as never)).toThrow();
  });

  it('addGroup creates a heading + ul and addItem fills the last group', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    const ul = menu.addGroup('Actions');
    expect(ul.tagName).toBe('UL');
    expect(menu.$container!.querySelector('h3')!.textContent).toBe('Actions');
    const el = menu.addItem({label: 'One'});
    expect(el.closest('ul')).toBe(ul);
  });

  it('getFirstDestructiveGroup finds the group with a destructive item', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    const safe = menu.addGroup();
    menu.addItem({label: 'Safe'}, safe);
    const danger = menu.addGroup();
    menu.addItem({label: 'Delete', destructive: true}, danger);
    expect(menu.getFirstDestructiveGroup()).toBe(danger);
  });

  it('removeItem drops the item and empties the group', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    const ul = menu.addGroup();
    const el = menu.addItem({label: 'Lonely'}, ul);
    menu.removeItem(el);
    expect(document.body.contains(ul)).toBe(false);
  });

  it('addHr inserts a horizontal rule', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    const hr = menu.addHr();
    expect(hr.tagName).toBe('HR');
    expect(menu.$container!.contains(hr)).toBe(true);
  });

  it('hasVisibleItems reflects whether any item is shown', () => {
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    const el = menu.addItem({label: 'Visible'});
    expect(menu.hasVisibleItems()).toBe(true);
    menu.hideItem(el);
    expect(menu.hasVisibleItems()).toBe(false);
  });
});

describe('DisclosureMenu item selection', () => {
  it('runs onActivate and hides the menu on activate', () => {
    vi.useFakeTimers();
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    const onActivate = vi.fn();
    const el = menu.addItem({label: 'Go', onActivate});
    makeVisible(el);
    menu.show();

    el.dispatchEvent(new CustomEvent('activate'));
    expect(onActivate).toHaveBeenCalledOnce();
    expect(onActivate).toHaveBeenCalledWith(el);

    // hide() is scheduled a tick later.
    vi.advanceTimersByTime(1);
    expect(menu.isExpanded()).toBe(false);
  });

  it('falls back to the legacy `callback` when onActivate is absent', () => {
    vi.useFakeTimers();
    const {trigger} = buildMenu();
    const menu = new DisclosureMenu(trigger);
    const callback = vi.fn();
    const el = menu.addItem({label: 'Go', callback});
    el.dispatchEvent(new CustomEvent('activate'));
    expect(callback).toHaveBeenCalledOnce();
  });
});

describe('DisclosureMenu search input', () => {
  it('builds a search input when withSearchInput is set', () => {
    const {trigger} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger, {withSearchInput: true});
    expect(menu.$searchInput).not.toBeNull();
    expect(menu.$container!.querySelector('.search-container')).not.toBeNull();
  });

  it('enables search via the data-with-search-input attribute', () => {
    const {trigger, container} = buildMenu(['Alpha']);
    container.setAttribute('data-with-search-input', '');
    const menu = new DisclosureMenu(trigger);
    expect(menu.$searchInput).not.toBeNull();
  });

  it('filters items by typed text and clears the filter', () => {
    const {trigger, container} = buildMenu(['Apple', 'Banana']);
    const menu = new DisclosureMenu(trigger, {withSearchInput: true});

    menu.$searchInput!.value = 'app';
    menu.$searchInput!.dispatchEvent(new Event('input'));

    const items = Array.from(container.querySelectorAll('li'));
    const apple = items.find((li) => li.textContent!.includes('Apple'))!;
    const banana = items.find((li) => li.textContent!.includes('Banana'))!;
    expect(apple.classList.contains('filtered')).toBe(false);
    expect(banana.classList.contains('filtered')).toBe(true);

    menu.$searchInput!.value = '';
    menu.$searchInput!.dispatchEvent(new Event('input'));
    expect(banana.classList.contains('filtered')).toBe(false);
  });

  it('Return inside the search input does not submit (preventDefault)', () => {
    const {trigger} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger, {withSearchInput: true});
    const ev = new KeyboardEvent('keydown', {
      keyCode: RETURN_KEY,
      cancelable: true,
    } as never);
    menu.$searchInput!.dispatchEvent(ev);
    expect(ev.defaultPrevented).toBe(true);
  });
});

describe('DisclosureMenu positioning (mocked layout)', () => {
  it('positions below the trigger when there is room and sets top/left', () => {
    const {trigger, container} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger);
    // Trigger near the top; menu short → fits below.
    mockRect(trigger, 50, 100, 80, 24);
    mockRect(container, 0, 0, 160, 120);
    menu.$alignmentElement = trigger;

    menu.setContainerPosition();

    // top is the trigger's bottom (offset.top 50 + height 24 = 74).
    expect(container.style.top).toBe('74px');
    expect(container.style.left).not.toBe('');
  });

  it('positions above the trigger when there is no room below', () => {
    const {trigger, container} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger);
    // Trigger near the bottom of an 768px viewport, tall menu → must flip above.
    mockRect(trigger, 740, 100, 80, 24);
    mockRect(container, 0, 0, 160, 400);
    menu.$alignmentElement = trigger;

    menu.setContainerPosition();

    // Above → top is less than the trigger's own top (740).
    expect(parseFloat(container.style.top)).toBeLessThan(740);
  });

  it('honors position: "below" even when above has more room', () => {
    const {trigger, container} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger, {position: 'below'});
    mockRect(trigger, 700, 100, 80, 24);
    mockRect(container, 0, 0, 160, 400);
    menu.$alignmentElement = trigger;

    menu.setContainerPosition();
    // Forced below → top is the trigger bottom (724).
    expect(container.style.top).toBe('724px');
  });
});

describe('DisclosureMenu destroy', () => {
  it('drops registrations, removes from instances, fires destroy', () => {
    const {trigger, container} = buildMenu(['Alpha']);
    const menu = new DisclosureMenu(trigger);
    const onDestroy = vi.fn();
    menu.on('destroy', onDestroy);

    menu.destroy();

    expect(onDestroy).toHaveBeenCalledOnce();
    expect(DisclosureMenu.instances).not.toContain(menu);
    expect(DisclosureMenu.getInstance(trigger)).toBeUndefined();
    expect(DisclosureMenu.getInstance(container)).toBeUndefined();
  });
});
