import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {releaseFocusWithin, trapFocusWithin} from '../src/utils/focus';

let container: HTMLElement;

beforeEach(() => {
  vi.spyOn(Element.prototype, 'getClientRects').mockImplementation(
    () => [{width: 10, height: 10}] as unknown as DOMRectList
  );

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
  trapFocusWithin(container);
});

afterEach(() => {
  releaseFocusWithin(container);
  vi.restoreAllMocks();
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

describe('trapFocusWithin', () => {
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
