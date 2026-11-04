import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {focusStaysWithin} from './focus-out.js';

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
