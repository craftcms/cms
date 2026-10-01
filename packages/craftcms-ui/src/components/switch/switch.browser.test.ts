import {beforeEach, expect, it} from 'vite-plus/test';
import type CraftSwitch from './switch.js';
import './switch.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('is only as tall as its switch', async () => {
  await import('../../styles/shared/color-palette.css');
  await import('../../styles/shared/colorable.css');
  await import('../../styles/shared/variables.css');
  await import('../../styles/shared/tokens.css');

  for (const size of ['small', 'medium']) {
    document.body.innerHTML = `<craft-switch label="Enabled" size="${size}"></craft-switch>`;
    const element = document.querySelector('craft-switch') as CraftSwitch;
    await element.updateComplete;

    const group = element.shadowRoot!.querySelector('.form-field__group-two')!;
    const button = element.querySelector('craft-switch-button')!;

    const height = button.getBoundingClientRect().height;

    expect(height, size).toBeGreaterThan(0);
    expect(group.getBoundingClientRect().height, size).toBe(height);
  }
});
