import {describe, expect, it} from 'vite-plus/test';
import {createInitialIcon, initialOf} from './initialIcon';

describe('initialOf', () => {
  it('uppercases the first character', () => {
    expect(initialOf('  details')).toBe('D');
    expect(initialOf('éditions')).toBe('É');
  });

  it('falls back to a question mark', () => {
    expect(initialOf(' ')).toBe('?');
  });
});

describe('createInitialIcon', () => {
  it('shows the initial, hidden from assistive technology', () => {
    const $icon = createInitialIcon('<Meta>');

    expect($icon.className).toBe('initial-icon');
    expect($icon.getAttribute('aria-hidden')).toBe('true');
    expect($icon.textContent).toBe('<');
  });
});
