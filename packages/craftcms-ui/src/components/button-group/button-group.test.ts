import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftButton from '../button/button.js';
import '../button/button.js';
import './button-group.js';

const setFormValue = vi.fn();
const attachInternals = Object.getOwnPropertyDescriptor(
  HTMLElement.prototype,
  'attachInternals'
);

beforeEach(() => {
  document.body.innerHTML = '';
  setFormValue.mockClear();
  Object.defineProperty(HTMLElement.prototype, 'attachInternals', {
    configurable: true,
    value: () => ({setFormValue}),
  });
});

afterEach(() => {
  if (attachInternals) {
    Object.defineProperty(
      HTMLElement.prototype,
      'attachInternals',
      attachInternals
    );
  } else {
    delete (HTMLElement.prototype as Partial<HTMLElement>).attachInternals;
  }
});

describe('craft-button-group', () => {
  it('toggles multiple selected values', async () => {
    const group = document.createElement('craft-button-group');
    group.name = 'topics';
    group.multiple = true;

    const button = document.createElement('craft-button');
    button.value = 'news';
    button.textContent = 'News';
    group.append(button);
    document.body.append(group);
    await Promise.all([group.updateComplete, button.updateComplete]);

    let values: string[] = [];
    group.addEventListener('change', (event) => {
      values = (event as CustomEvent<{values: string[]}>).detail.values;
    });

    button.dispatchEvent(
      new MouseEvent('click', {bubbles: true, composed: true})
    );

    expect((button as CraftButton).active).toBe(true);
    expect(values).toEqual(['news']);
    expect(setFormValue).toHaveBeenCalled();
  });
});
