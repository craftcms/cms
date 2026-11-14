import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {
  composedContains,
  containsFocus,
  focusStaysWithin,
} from './focus-out.js';

function focusOut(relatedTarget: EventTarget | null): FocusEvent {
  return new FocusEvent('focusout', {relatedTarget});
}

describe('focusStaysWithin', () => {
  let region: HTMLElement;
  let inside: HTMLButtonElement;
  let outside: HTMLButtonElement;

  beforeEach(() => {
    document.body.innerHTML = `
      <div class="region"><button class="inside">In</button></div>
      <button class="outside">Out</button>
    `;
    region = document.querySelector('.region')!;
    inside = document.querySelector('.inside')!;
    outside = document.querySelector('.outside')!;
  });

  it('counts focus moving to a descendant, or to the region itself', () => {
    expect(focusStaysWithin(focusOut(inside), region)).toBe(true);
    expect(focusStaysWithin(focusOut(region), region)).toBe(true);
  });

  it('counts focus moving into any of several regions', () => {
    expect(focusStaysWithin(focusOut(outside), region, outside)).toBe(true);
  });

  it('counts focus moving into a region’s own shadow tree', () => {
    const shadowButton = document.createElement('button');
    region.attachShadow({mode: 'open'}).append(shadowButton);

    expect(focusStaysWithin(focusOut(shadowButton), region)).toBe(true);
  });

  it('doesn’t count focus moving outside every region', () => {
    expect(focusStaysWithin(focusOut(outside), region)).toBe(false);
  });

  it('doesn’t count focus moving to nothing', () => {
    expect(focusStaysWithin(focusOut(null), region)).toBe(false);
  });

  it('skips a region that isn’t there', () => {
    expect(focusStaysWithin(focusOut(inside), undefined, null, region)).toBe(
      true
    );
  });
});

describe('composedContains', () => {
  it('counts the node itself, its descendants, and nodes inside its shadow tree', () => {
    document.body.innerHTML =
      '<div class="host"><span class="child"></span></div>';
    const host = document.querySelector('.host')!;
    const inner = document.createElement('input');
    host.attachShadow({mode: 'open'}).append(inner);

    expect(composedContains(host, host)).toBe(true);
    expect(composedContains(host, host.querySelector('.child')!)).toBe(true);
    expect(composedContains(host, inner)).toBe(true);
    expect(composedContains(document.body.querySelector('.child')!, host)).toBe(
      false
    );
  });
});

it('composedContains follows a node into the slot it’s assigned to', () => {
  document.body.innerHTML = '<div class="host"><input class="slotted"></div>';
  const host = document.querySelector('.host')!;
  const shadow = host.attachShadow({mode: 'open'});
  shadow.innerHTML = '<div class="wrapper"><slot></slot></div>';
  const slotted = document.querySelector('.slotted')!;
  // happy-dom doesn't assign slots, so give the input the one a browser would.
  Object.defineProperty(slotted, 'assignedSlot', {
    value: shadow.querySelector('slot'),
  });

  expect(composedContains(shadow.querySelector('.wrapper')!, slotted)).toBe(
    true
  );
});

describe('containsFocus', () => {
  it('is true when the node or something inside it has focus', () => {
    document.body.innerHTML =
      '<div class="box"><button class="inside">In</button></div><button class="outside">Out</button>';
    const box = document.querySelector<HTMLElement>('.box')!;

    document.querySelector<HTMLElement>('.inside')!.focus();
    expect(containsFocus(box)).toBe(true);

    document.querySelector<HTMLElement>('.outside')!.focus();
    expect(containsFocus(box)).toBe(false);
  });

  it('reads focus inside a shadow root', () => {
    document.body.innerHTML = '<div class="host"></div>';
    const root = document.querySelector('.host')!.attachShadow({mode: 'open'});
    root.innerHTML = '<button>In</button>';
    const button = root.querySelector('button')!;

    button.focus();
    expect(containsFocus(button)).toBe(true);
  });
});
