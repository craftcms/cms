import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {focusableWithin, trapFocus} from './focus-trap.js';

/** A host with chrome in its shadow root and a slot in the middle, like a dialog. */
class TrapHost extends HTMLElement {
  connectedCallback() {
    if (this.shadowRoot) {
      return;
    }

    const root = this.attachShadow({mode: 'open'});
    root.innerHTML = `<button class="close">Close</button><slot></slot>`;
  }
}

if (!customElements.get('trap-host')) {
  customElements.define('trap-host', TrapHost);
}

function createHost(lightDom = ''): TrapHost {
  const host = document.createElement('trap-host') as TrapHost;
  host.innerHTML = lightDom;
  document.body.append(host);
  return host;
}

function tab(host: HTMLElement, from: HTMLElement, shiftKey = false): void {
  from.focus();
  from.dispatchEvent(
    // `composed` matters: a non-composed event dispatched in a shadow tree
    // never reaches the host, where the trap listens.
    new KeyboardEvent('keydown', {
      key: 'Tab',
      shiftKey,
      bubbles: true,
      composed: true,
    })
  );
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('focusableWithin', () => {
  it('collects across the shadow tree and slotted light DOM', () => {
    const host = createHost(
      '<button id="a">A</button><button id="b">B</button>'
    );

    expect(focusableWithin(host).map((el) => el.className || el.id)).toEqual([
      'close',
      'a',
      'b',
    ]);
  });

  it('skips disabled, inert and aria-hidden elements', () => {
    const host = createHost(`
      <button id="a" disabled>A</button>
      <button id="b" inert>B</button>
      <button id="c" aria-hidden="true">C</button>
      <button id="d">D</button>
    `);

    expect(focusableWithin(host).map((el) => el.className || el.id)).toEqual([
      'close',
      'd',
    ]);
  });

  it('skips tabindex="-1" elements', () => {
    const host = createHost(
      '<button id="a" tabindex="-1">A</button><button id="b">B</button>'
    );

    expect(focusableWithin(host).map((el) => el.className || el.id)).toEqual([
      'close',
      'b',
    ]);
  });
});

describe('trapFocus', () => {
  it('wraps Tab from the last element to the first', () => {
    const host = createHost(
      '<button id="a">A</button><button id="b">B</button>'
    );
    trapFocus(host);

    const last = host.querySelector<HTMLButtonElement>('#b')!;
    tab(host, last);

    expect(host.shadowRoot!.activeElement).toBe(
      host.shadowRoot!.querySelector('.close')
    );
  });

  it('wraps Shift-Tab from the first element to the last', () => {
    const host = createHost(
      '<button id="a">A</button><button id="b">B</button>'
    );
    trapFocus(host);

    const first = host.shadowRoot!.querySelector<HTMLButtonElement>('.close')!;
    tab(host, first, true);

    expect(document.activeElement).toBe(host.querySelector('#b'));
  });

  it('leaves interior Tab presses alone', () => {
    const host = createHost(
      '<button id="a">A</button><button id="b">B</button>'
    );
    trapFocus(host);

    const middle = host.querySelector<HTMLButtonElement>('#a')!;
    tab(host, middle);

    // No wrap: focus stays where the browser would take it from here.
    expect(document.activeElement).toBe(middle);
  });

  it('stops trapping once released', () => {
    const host = createHost(
      '<button id="a">A</button><button id="b">B</button>'
    );
    const release = trapFocus(host);
    release();

    const last = host.querySelector<HTMLButtonElement>('#b')!;
    tab(host, last);

    expect(document.activeElement).toBe(last);
  });

  it('does nothing when there is nothing to focus', () => {
    const host = document.createElement('div');
    document.body.append(host);
    trapFocus(host);

    expect(() =>
      host.dispatchEvent(new KeyboardEvent('keydown', {key: 'Tab'}))
    ).not.toThrow();
  });
});
