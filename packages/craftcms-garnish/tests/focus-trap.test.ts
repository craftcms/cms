import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {releaseFocusWithin, trapFocusWithin} from '../src/utils/focus';

/** Shadow content for each stand-in component the dialogs below use. */
const COMPONENTS: Record<string, string> = {
  'x-pair': '<input class="one"><input class="two">',
  'x-clearable':
    '<slot name="input"></slot><button class="clear">Clear</button>',
  'x-hidden-last':
    '<input class="one"><button class="gone" hidden>Gone</button>',
  'x-disabled-last':
    '<input class="one"><button class="off" disabled>Off</button>',
  'x-nested': '<x-pair class="inner"></x-pair>',
  'x-fallback': '<slot><button class="fallback">Default</button></slot>',
};

const traps: Element[] = [];
let container: HTMLElement;

beforeEach(() => {
  // happy-dom has no layout, so every element reports a box unless it's `hidden`.
  vi.spyOn(Element.prototype, 'getClientRects').mockImplementation(
    function (this: Element) {
      return (this.hasAttribute('hidden')
        ? []
        : [{width: 10, height: 10}]) as unknown as DOMRectList;
    }
  );
});

afterEach(() => {
  traps.splice(0).forEach(releaseFocusWithin);
  vi.restoreAllMocks();
  document.body.innerHTML = '';
});

function trap(element: Element): void {
  trapFocusWithin(element);
  traps.push(element);
}

function attachComponents(root: ParentNode): void {
  for (const host of root.querySelectorAll(
    Object.keys(COMPONENTS).join(', ')
  )) {
    const shadow = host.attachShadow({mode: 'open'});
    shadow.innerHTML = COMPONENTS[host.localName]!;
    attachComponents(shadow);
  }
}

/** Renders a trapped dialog with `content`. */
function render(content: string): void {
  document.body.innerHTML = `<button class="outside">Behind</button><div class="dialog" tabindex="-1">${content}</div>`;
  attachComponents(document.body);
  container = document.querySelector('.dialog')!;
  trap(container);
}

/** Finds an element, following `>>>` into a component's shadow root. */
function el(path: string): HTMLElement {
  let root: ParentNode = document;
  let found: Element | null = null;
  for (const selector of path.split('>>>')) {
    found = root.querySelector(selector.trim());
    root = found!.shadowRoot ?? found!;
  }
  return found as HTMLElement;
}

/** The focused element, looking inside shadow roots. */
function focused(): Element | null {
  let active = document.activeElement;
  while (active?.shadowRoot?.activeElement) {
    active = active.shadowRoot.activeElement;
  }
  return active;
}

/** Presses Tab on `target`, returning whether a trap took it over. */
function pressTab(target: HTMLElement, {shift = false} = {}): boolean {
  target.focus();
  const event = new KeyboardEvent('keydown', {
    key: 'Tab',
    keyCode: 9,
    shiftKey: shift,
    bubbles: true,
    composed: true,
    cancelable: true,
  });
  target.dispatchEvent(event);
  return event.defaultPrevented;
}

describe('trapFocusWithin', () => {
  beforeEach(() => {
    render(`
      <h1 tabindex="-1">Setup</h1>
      <p>Instructions</p>
      <a class="first" href="/app">App</a>
      <input class="middle">
      <button class="save">Save</button>
      <x-pair class="pair"></x-pair>
    `);
  });

  it('wraps Shift+Tab from the first Tab stop to the last, past a focused heading before it', () => {
    expect(pressTab(el('.first'), {shift: true})).toBe(true);
    expect(focused()).toBe(el('.pair >>> .two'));
  });

  it('wraps Shift+Tab from a heading before the first Tab stop to the last', () => {
    expect(pressTab(el('h1'), {shift: true})).toBe(true);
    expect(focused()).toBe(el('.pair >>> .two'));
  });

  it('wraps Shift+Tab from the dialog itself to the last Tab stop, and leaves Tab to the browser', () => {
    expect(pressTab(container, {shift: true})).toBe(true);
    expect(focused()).toBe(el('.pair >>> .two'));
    expect(pressTab(container)).toBe(false);
  });

  it('wraps Tab from the last Tab stop, inside a component, to the first, not the heading', () => {
    expect(pressTab(el('.pair >>> .two'))).toBe(true);
    expect(focused()).toBe(el('.first'));
  });

  it('leaves Tab between the controls inside a component to the browser', () => {
    expect(pressTab(el('.pair >>> .one'))).toBe(false);
    expect(pressTab(el('.pair >>> .two'), {shift: true})).toBe(false);
  });

  it('leaves Tab and Shift+Tab between stops to the browser', () => {
    expect(pressTab(el('h1'))).toBe(false);
    expect(pressTab(el('.middle'))).toBe(false);
    expect(pressTab(el('.save'))).toBe(false);
    expect(pressTab(el('.middle'), {shift: true})).toBe(false);
  });
});

it('wraps Shift+Tab from the first control inside a leading component, but not from its second', () => {
  render(`
    <h1 tabindex="-1">Setup</h1>
    <x-pair class="lead"></x-pair>
    <button class="save">Save</button>
  `);

  expect(pressTab(el('.lead >>> .two'), {shift: true})).toBe(false);
  expect(pressTab(el('.lead >>> .one'), {shift: true})).toBe(true);
  expect(focused()).toBe(el('.save'));
});

describe('a component that renders a slotted control before its own button', () => {
  it('wraps Tab from its button, not from the slotted control, when it comes last', () => {
    render(`
      <a class="first" href="/app">App</a>
      <x-clearable class="combo"><input slot="input" class="field"></x-clearable>
    `);

    expect(pressTab(el('.field'))).toBe(false);
    expect(pressTab(el('.combo >>> .clear'))).toBe(true);
    expect(focused()).toBe(el('.first'));
  });

  it('wraps Shift+Tab from the slotted control, not from its button, when it comes first', () => {
    render(`
      <x-clearable class="combo"><input slot="input" class="field"></x-clearable>
      <button class="save">Save</button>
    `);

    expect(pressTab(el('.combo >>> .clear'), {shift: true})).toBe(false);
    expect(pressTab(el('.field'), {shift: true})).toBe(true);
    expect(focused()).toBe(el('.save'));
  });

  it('doesn’t count a child that no slot shows', () => {
    render(`
      <a class="first" href="/app">App</a>
      <x-clearable class="combo"><input slot="input" class="field"><input class="unslotted"></x-clearable>
    `);

    expect(pressTab(el('.combo >>> .clear'))).toBe(true);
    expect(focused()).toBe(el('.first'));
  });
});

it('wraps Tab from an element after the last Tab stop that’s only focused from code', () => {
  render(`
    <a class="first" href="/app">App</a>
    <button class="save">Save</button>
    <p class="status" tabindex="-1">Saved</p>
  `);

  expect(pressTab(el('.status'))).toBe(true);
  expect(focused()).toBe(el('.first'));
});

it('counts a slot’s fallback content only when nothing, not even text, is slotted', () => {
  render(`
    <a class="first" href="/app">App</a>
    <x-fallback class="empty"></x-fallback>
  `);
  expect(pressTab(el('.empty >>> .fallback'))).toBe(true);
  expect(focused()).toBe(el('.first'));

  render(`
    <a class="first" href="/app">App</a>
    <x-fallback class="text">Text only</x-fallback>
  `);
  expect(pressTab(el('.first'))).toBe(true);
  expect(focused()).toBe(el('.first'));
});

it('skips a hidden or disabled control inside a component when finding the last Tab stop', () => {
  render(`
    <a class="first" href="/app">App</a>
    <x-hidden-last class="hidden-last"></x-hidden-last>
  `);
  expect(pressTab(el('.hidden-last >>> .one'))).toBe(true);
  expect(focused()).toBe(el('.first'));

  render(`
    <a class="first" href="/app">App</a>
    <x-disabled-last class="disabled-last"></x-disabled-last>
  `);
  expect(pressTab(el('.disabled-last >>> .one'))).toBe(true);
  expect(focused()).toBe(el('.first'));
});

it('wraps Tab from a control inside a component inside another component', () => {
  render(`
    <a class="first" href="/app">App</a>
    <x-nested class="outer"></x-nested>
  `);

  expect(pressTab(el('.outer >>> .inner >>> .one'))).toBe(false);
  expect(pressTab(el('.outer >>> .inner >>> .two'))).toBe(true);
  expect(focused()).toBe(el('.first'));
});

it('keeps focus on the only Tab stop', () => {
  render('<h1 tabindex="-1">Setup</h1><button class="only">Close</button>');

  expect(pressTab(el('.only'))).toBe(true);
  expect(focused()).toBe(el('.only'));
  expect(pressTab(el('.only'), {shift: true})).toBe(true);
  expect(focused()).toBe(el('.only'));
});

describe('a trap inside a trap', () => {
  beforeEach(() => {
    render(`
      <a class="first" href="/app">App</a>
      <aside class="sidebar">
        <button class="s1">One</button>
        <button class="s2">Two</button>
        <button class="s3">Three</button>
      </aside>
    `);
    trap(el('.sidebar'));
  });

  it('wraps within the inner trap at its edges', () => {
    expect(pressTab(el('.s3'))).toBe(true);
    expect(focused()).toBe(el('.s1'));
    expect(pressTab(el('.s1'), {shift: true})).toBe(true);
    expect(focused()).toBe(el('.s3'));
  });

  it('leaves Tab between the inner trap’s stops to the browser', () => {
    expect(pressTab(el('.s2'))).toBe(false);
    expect(pressTab(el('.s2'), {shift: true})).toBe(false);
  });
});
