import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import {
  getFocusableElements,
  isFocusable,
  isKeyboardFocusable,
} from '../src/utils/focusable';

// happy-dom doesn't compute layout, so offsetWidth/Height are 0. Force the
// visibility check to pass by stubbing getClientRects to report a box and
// getComputedStyle visibility to 'visible'.
function forceVisible(): void {
  vi.spyOn(Element.prototype, 'getClientRects').mockImplementation(
    () => [{width: 10, height: 10}] as unknown as DOMRectList
  );
}

describe('isFocusable', () => {
  let container: HTMLElement;

  beforeEach(() => {
    forceVisible();
    container = document.createElement('div');
    document.body.appendChild(container);
  });

  it('treats an <a href> as focusable', () => {
    const a = document.createElement('a');
    a.setAttribute('href', '/x');
    container.appendChild(a);
    expect(isFocusable(a)).toBe(true);
  });

  it('treats an <a> without href and without tabindex as NOT focusable', () => {
    const a = document.createElement('a');
    container.appendChild(a);
    expect(isFocusable(a)).toBe(false);
  });

  it('treats an enabled input as focusable, a disabled one as not', () => {
    const input = document.createElement('input');
    container.appendChild(input);
    expect(isFocusable(input)).toBe(true);

    input.disabled = true;
    expect(isFocusable(input)).toBe(false);
  });

  it('treats a plain div as focusable only with a tabindex', () => {
    const div = document.createElement('div');
    container.appendChild(div);
    expect(isFocusable(div)).toBe(false);

    div.setAttribute('tabindex', '0');
    expect(isFocusable(div)).toBe(true);

    div.setAttribute('tabindex', '-1');
    expect(isFocusable(div)).toBe(true); // still focusable (just not by keyboard)
  });
});

describe('isKeyboardFocusable', () => {
  beforeEach(() => forceVisible());

  it('excludes tabindex="-1"', () => {
    const div = document.createElement('div');
    div.setAttribute('tabindex', '-1');
    document.body.appendChild(div);
    expect(isFocusable(div)).toBe(true);
    expect(isKeyboardFocusable(div)).toBe(false);
  });

  it('includes tabindex="0"', () => {
    const div = document.createElement('div');
    div.setAttribute('tabindex', '0');
    document.body.appendChild(div);
    expect(isKeyboardFocusable(div)).toBe(true);
  });
});

describe('getFocusableElements', () => {
  beforeEach(() => forceVisible());

  it('returns focusable descendants in document order, excluding the container', () => {
    const container = document.createElement('div');
    container.setAttribute('tabindex', '0'); // container itself is focusable
    container.innerHTML =
      '<a href="/a">a</a><span>x</span><button>b</button><input disabled>';
    document.body.appendChild(container);

    const focusable = getFocusableElements(container);
    const tags = focusable.map((el) => el.tagName.toLowerCase());
    expect(tags).toEqual(['a', 'button']);
  });
});
