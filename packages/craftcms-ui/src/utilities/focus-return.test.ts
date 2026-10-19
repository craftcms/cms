import {afterEach, describe, expect, it} from 'vite-plus/test';
import {focusReturnFor, isFocusable} from './focus-return.js';

function menu(key?: string): {
  host: HTMLElement;
  invoker: HTMLButtonElement;
  item: HTMLButtonElement;
} {
  const host = document.createElement('craft-action-menu');
  if (key) {
    host.setAttribute('data-focus-key', key);
  }
  host.innerHTML = `
    <button slot="invoker">Actions</button>
    <div slot="content" hidden><button>Copy</button></div>
  `;

  return {
    host,
    invoker: host.querySelector('[slot="invoker"]')!,
    item: host.querySelector('[slot="content"] button')!,
  };
}

function scope(key: string): HTMLElement {
  const el = document.createElement('div');
  el.setAttribute('data-focus-scope', key);
  document.body.append(el);
  return el;
}

afterEach(() => {
  document.body.innerHTML = '';
});

describe('isFocusable', () => {
  it('accepts a connected, visible, enabled element', () => {
    const button = document.createElement('button');
    document.body.append(button);

    expect(isFocusable(button)).toBe(true);
  });

  it('rejects disconnected, hidden, inert and disabled elements', () => {
    const detached = document.createElement('button');

    const hidden = document.createElement('div');
    hidden.hidden = true;
    hidden.append(document.createElement('button'));

    const inert = document.createElement('div');
    inert.inert = true;
    inert.append(document.createElement('button'));

    const disabled = document.createElement('button');
    disabled.disabled = true;

    document.body.append(hidden, inert, disabled);

    expect(isFocusable(detached)).toBe(false);
    expect(isFocusable(hidden.firstElementChild)).toBe(false);
    expect(isFocusable(inert.firstElementChild)).toBe(false);
    expect(isFocusable(disabled)).toBe(false);
    expect(isFocusable(null)).toBe(false);
  });

  it('rejects an element in a shadow root whose host is inside an inert element', () => {
    const inert = document.createElement('div');
    inert.inert = true;
    const host = document.createElement('div');
    const button = document.createElement('button');
    host.attachShadow({mode: 'open'}).append(button);
    inert.append(host);
    document.body.append(inert);

    expect(isFocusable(button)).toBe(false);
  });
});

describe('focusReturnFor', () => {
  it('returns the trigger when it can still take focus', () => {
    const button = document.createElement('button');
    document.body.append(button);

    expect(focusReturnFor(button)()).toBe(button);
  });

  it('returns the menu invoker when the trigger is a hidden menu item', () => {
    const {host, invoker, item} = menu();
    document.body.append(host);

    expect(focusReturnFor(item)()).toBe(invoker);
  });

  it('returns the invoker itself when it was the trigger', () => {
    const {host, invoker} = menu();
    document.body.append(host);

    expect(focusReturnFor(invoker)()).toBe(invoker);
  });

  it('returns the nearest menu invoker for a nested menu', () => {
    const outer = menu();
    const inner = menu();
    const outerContent =
      outer.host.querySelector<HTMLElement>('[slot="content"]')!;
    outerContent.hidden = false;
    outerContent.append(inner.host);
    document.body.append(outer.host);

    expect(focusReturnFor(inner.item)()).toBe(inner.invoker);
  });

  it('returns the outer menu invoker once a nested menu has closed too', () => {
    const outer = menu();
    const inner = menu();
    outer.host.querySelector('[slot="content"]')!.append(inner.host);
    document.body.append(outer.host);

    expect(focusReturnFor(inner.item)()).toBe(outer.invoker);
  });

  it('finds the re-rendered menu by its focus key', () => {
    const original = menu('field:menu');
    document.body.append(original.host);
    const resolve = focusReturnFor(original.item);

    const replacement = menu('field:menu');
    original.host.replaceWith(replacement.host);

    expect(resolve()).toBe(replacement.invoker);
  });

  it('only matches a re-rendered menu inside the same focus scope', () => {
    const panel = scope('slideout-1');
    const original = menu('field:menu');
    panel.append(original.host);
    const resolve = focusReturnFor(original.item);

    const onPage = menu('field:menu');
    document.body.prepend(onPage.host);
    const inOtherPanel = menu('field:menu');
    scope('slideout-2').append(inOtherPanel.host);
    const replacement = menu('field:menu');
    original.host.replaceWith(replacement.host);

    expect(resolve()).toBe(replacement.invoker);
  });

  it('returns null when nothing can take focus', () => {
    const {host, item} = menu('field:menu');
    document.body.append(host);
    const resolve = focusReturnFor(item);
    host.remove();

    expect(resolve()).toBeNull();
    expect(focusReturnFor(null)()).toBeNull();
  });
});
