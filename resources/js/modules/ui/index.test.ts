import $ from 'jquery';
import {afterEach, expect, it, vi} from 'vite-plus/test';

afterEach(() => {
  vi.unstubAllGlobals();
  vi.resetModules();
});

it('creates text inputs without the legacy Garnish behavior', async () => {
  vi.stubGlobal('$', $);
  vi.stubGlobal('Craft', {ui: {}});

  await import('./index');

  const input = window.Craft.ui.createTextInput({
    name: 'label',
    value: 'Option',
  })[0];

  expect(input).toBeInstanceOf(HTMLInputElement);
  expect(input.name).toBe('label');
  expect(input.value).toBe('Option');
});

it('renders a legacy chromeless button without a fill', async () => {
  vi.stubGlobal('$', $);
  vi.stubGlobal('Craft', {ui: {}});

  await import('./index');

  const button = window.Craft.ui.createButton({
    icon: 'xmark',
    label: 'Cancel',
    class: 'chromeless notification-close-btn',
  })[0];

  expect(button.variant).toBe('plain');
});
