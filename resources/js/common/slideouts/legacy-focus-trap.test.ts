import $ from 'jquery';
import {
  afterEach,
  beforeAll,
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vite-plus/test';
import Garnish from '../../../../packages/craftcms-legacy/garnish/src/Garnish.js';

interface LegacyGarnish {
  trapFocusWithin(container: Element): void;
  releaseFocusWithin(container: Element): void;
}

const garnish = Garnish as unknown as LegacyGarnish;
let container: HTMLElement;

beforeAll(async () => {
  // The legacy bundle gets `:focusable` from jQuery UI, which reads the global.
  Object.assign(globalThis, {jQuery: $});
  ($ as unknown as {ui: object}).ui ??= {};
  await import('jquery-ui/ui/focusable.js');
});

interface JquerySelectorEngine {
  matches(selector: string, elements: Element[]): Element[];
  matchesSelector(element: Element, selector: string): boolean;
}

/**
 * happy-dom matches nothing for a selector it doesn't know, where a browser
 * throws, so jQuery never falls back to its own engine for `:focusable`.
 */
function rejectJquerySelectors(): void {
  const native = Object.getOwnPropertyDescriptor(
    Element.prototype,
    'querySelectorAll'
  )!.value as (this: Element, selector: string) => NodeListOf<Element>;
  vi.spyOn(Element.prototype, 'querySelectorAll').mockImplementation(function (
    this: Element,
    selector: string
  ) {
    if (/:(focusable|visible)\b/.test(selector)) {
      throw new SyntaxError(`Unsupported selector: ${selector}`);
    }
    return native.call(this, selector);
  } as never);

  const engine = ($ as unknown as {find: JquerySelectorEngine}).find;
  vi.spyOn(engine, 'matchesSelector').mockImplementation(
    (element, selector) => engine.matches(selector, [element]).length > 0
  );
}

beforeEach(() => {
  rejectJquerySelectors();
  vi.spyOn(Element.prototype, 'getClientRects').mockImplementation(
    () => [{width: 10, height: 10}] as unknown as DOMRectList
  );

  // jQuery UI also wants `visibility` to compute, which needs a rule here.
  document.head.innerHTML = '<style>* { visibility: visible; }</style>';
  document.body.innerHTML = `
    <button class="outside">Behind</button>
    <div class="dialog">
      <h1 tabindex="-1">Setup</h1>
      <p>Instructions</p>
      <a class="first" href="/app">App</a>
      <input class="middle">
      <button class="last">Save</button>
      <x-control class="component"></x-control>
    </div>
  `;
  container = document.querySelector('.dialog')!;
  container.querySelector('x-control')!.attachShadow({mode: 'open'}).innerHTML =
    '<input>';
  garnish.trapFocusWithin(container);
});

afterEach(() => {
  garnish.releaseFocusWithin(container);
  vi.restoreAllMocks();
  document.head.innerHTML = '';
  document.body.innerHTML = '';
});

function el(selector: string): HTMLElement {
  return document.querySelector(selector)!;
}

/** Presses Tab on `target`, returning whether the trap took it over. */
function pressTab(target: HTMLElement, {shift = false} = {}): boolean {
  target.focus();
  const event = new KeyboardEvent('keydown', {
    key: 'Tab',
    keyCode: 9,
    shiftKey: shift,
    bubbles: true,
    cancelable: true,
  });
  target.dispatchEvent(event);
  return event.defaultPrevented;
}

describe('legacy Garnish.trapFocusWithin', () => {
  it('wraps Shift+Tab from the first Tab stop to the last, past a focused heading before it', () => {
    expect(pressTab(el('.first'), {shift: true})).toBe(true);
    expect(document.activeElement).toBe(el('.last'));
  });

  it('wraps Shift+Tab from a heading before the first Tab stop to the last', () => {
    expect(pressTab(el('h1'), {shift: true})).toBe(true);
    expect(document.activeElement).toBe(el('.last'));
  });

  it('wraps Tab from the last Tab stop to the first, not the heading', () => {
    expect(pressTab(el('.last'))).toBe(true);
    expect(document.activeElement).toBe(el('.first'));
  });

  it('wraps Tab from a component with its control in its shadow root, after the last Tab stop, to the first', () => {
    expect(pressTab(el('.component'))).toBe(true);
    expect(document.activeElement).toBe(el('.first'));
  });

  it('leaves Tab and Shift+Tab between stops to the browser', () => {
    expect(pressTab(el('h1'))).toBe(false);
    expect(pressTab(el('.middle'))).toBe(false);
    expect(pressTab(el('.middle'), {shift: true})).toBe(false);
  });
});
