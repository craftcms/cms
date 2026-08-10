import {beforeEach, describe, expect, it} from 'vitest';
import type CraftInputHandle from './input-handle.js';
import './input-handle.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-handle', () => {
  it('forwards correction attributes to the native input', async () => {
    const element = document.createElement(
      'craft-input-handle'
    ) as CraftInputHandle;
    element.setAttribute('autocorrect', 'off');
    element.setAttribute('autocapitalize', 'off');
    document.body.append(element);
    await element.updateComplete;
    const input = element.querySelector('input[slot="input"]');

    expect(element.autocorrect).toBe(false);
    expect(input?.getAttribute('autocorrect')).toBe('off');
    expect(input?.getAttribute('autocapitalize')).toBe('off');
  });
});
