import {beforeEach, describe, expect, it} from 'vitest';
import {
  createSwitch,
  createSlidePicker,
  createInputColor,
  createInputPassword,
} from './index.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('createSwitch', () => {
  it('builds a craft-switch with the button and hidden input', () => {
    const el = createSwitch({name: 'enabled', on: true, id: 'sw'});

    expect(el.tagName.toLowerCase()).toBe('craft-switch');
    expect(el.hasAttribute('checked')).toBe(true);

    const button = el.querySelector('craft-switch-button[slot="input"]');
    expect(button?.id).toBe('sw');
    expect(button?.getAttribute('aria-checked')).toBe('true');

    const hidden = el.querySelector<HTMLInputElement>(
      'input[slot="hidden-input"]'
    );
    expect(hidden?.name).toBe('enabled');
    expect(hidden?.getAttribute('value')).toBe('1');
  });

  it('adds the fieldtoggle wiring when a toggle target is given', () => {
    const el = createSwitch({toggle: 'target'});
    const button = el.querySelector('craft-switch-button');
    expect(button?.classList.contains('fieldtoggle')).toBe(true);
    expect(button?.getAttribute('data-target')).toBe('target');
  });
});

describe('createSlidePicker', () => {
  it('sets the numeric props and a form-posting hidden input', () => {
    const el = createSlidePicker({
      min: 0,
      max: 100,
      step: 25,
      value: 50,
      name: 'cols',
    });

    expect(el.tagName.toLowerCase()).toBe('craft-slide-picker');
    expect(el.min).toBe(0);
    expect(el.max).toBe(100);
    expect(el.step).toBe(25);
    expect(el.value).toBe(50);

    const hidden = el.querySelector<HTMLInputElement>('input[type="hidden"]');
    expect(hidden?.name).toBe('cols');
  });

  it('resolves function min/max', () => {
    const el = createSlidePicker({min: () => 10, max: () => 90, step: 20});
    expect(el.min).toBe(10);
    expect(el.max).toBe(90);
  });
});

describe('createInputColor', () => {
  it('strips the # and slots a hex input, passing presets through', () => {
    const el = createInputColor({
      name: 'bg',
      value: '#7ab55c',
      presets: ['#ffffff'],
    });

    expect(el.tagName.toLowerCase()).toBe('craft-input-color');
    expect(el.getAttribute('name')).toBe('bg');
    expect(el.getAttribute('presets')).toContain('ffffff');

    const input = el.querySelector<HTMLInputElement>('input[slot="input"]');
    expect(input?.getAttribute('value')).toBe('7ab55c');
    expect(input?.classList.contains('color-input')).toBe(true);
  });
});

describe('createInputPassword', () => {
  it('slots a password input and mirrors control props on the host', () => {
    const el = createInputPassword({name: 'pw', disabled: true});

    expect(el.tagName.toLowerCase()).toBe('craft-input-password');
    expect(el.getAttribute('name')).toBe('pw');
    expect(el.hasAttribute('disabled')).toBe(true);

    const input = el.querySelector<HTMLInputElement>('input[slot="input"]');
    expect(input?.type).toBe('password');
    expect(input?.disabled).toBe(true);
  });
});
