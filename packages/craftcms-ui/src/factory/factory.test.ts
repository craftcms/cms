import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {
  createSwitch,
  createSlidePicker,
  createInputColor,
  createInputPassword,
  createTextInput,
  createCopyTextPrompt,
} from './index.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

it('leaves custom element registration to the application entry', () => {
  for (const tag of [
    'craft-dialog',
    'craft-input-color',
    'craft-input-password',
    'craft-slide-picker',
    'craft-switch',
  ]) {
    expect(customElements.get(tag)).toBeUndefined();
  }
});

describe('createCopyTextPrompt', () => {
  it('opens a craft-dialog with a readonly input and a copy button', () => {
    const dialog = createCopyTextPrompt({
      label: 'Full URL',
      value: 'https://x',
    });

    expect(dialog.tagName.toLowerCase()).toBe('craft-dialog');
    expect(dialog.getAttribute('label')).toBe('Full URL');
    expect(dialog.hasAttribute('open')).toBe(true);
    // appended to the document
    expect(dialog.isConnected).toBe(true);

    const input = dialog.querySelector<HTMLInputElement>(
      '.copytext input.text'
    );
    expect(input?.getAttribute('value')).toBe('https://x');
    expect(input?.readOnly).toBe(true);

    const copyBtn = dialog.querySelector('craft-copy-button');
    expect(copyBtn?.getAttribute('value')).toBe('https://x');
  });

  it('renders a textarea when config.textarea is set', () => {
    const dialog = createCopyTextPrompt({
      label: 'composer.json',
      value: '{"a":1}',
      textarea: true,
      class: 'code',
      rows: 10,
    });

    const textarea = dialog.querySelector<HTMLTextAreaElement>(
      '.copytext textarea.text.code'
    );
    expect(textarea).not.toBeNull();
    // happy-dom reflects `rows` as a string; real browsers return a number.
    expect(Number(textarea?.rows)).toBe(10);
    expect(textarea?.readOnly).toBe(true);
    expect(textarea?.value).toBe('{"a":1}');
  });
});

describe('createTextInput', () => {
  it('builds a plain .text input with the mapped attributes', () => {
    const el = createTextInput({
      name: 'title',
      value: 'hi',
      id: 'foo',
      maxlength: 10,
      placeholder: 'Enter…',
    });

    expect(el.tagName).toBe('INPUT');
    expect(el.type).toBe('text');
    expect(el.classList.contains('text')).toBe(true);
    // no explicit size → fullwidth; placeholder → nicetext marker
    expect(el.classList.contains('fullwidth')).toBe(true);
    expect(el.classList.contains('nicetext')).toBe(true);
    expect(el.name).toBe('title');
    expect(el.getAttribute('value')).toBe('hi');
    expect(el.id).toBe('foo');
    expect(el.getAttribute('maxlength')).toBe('10');
    // autocomplete defaults off
    expect(el.getAttribute('autocomplete')).toBe('off');
  });

  it('drops fullwidth when a size is given and marks password type', () => {
    const el = createTextInput({type: 'password', size: 20, disabled: true});
    expect(el.classList.contains('fullwidth')).toBe(false);
    expect(el.classList.contains('password')).toBe(true);
    expect(el.classList.contains('disabled')).toBe(true);
    expect(el.disabled).toBe(true);
    expect(el.getAttribute('size')).toBe('20');
  });

  it('applies inputAttributes: booleans, aria/data maps, and class objects', () => {
    const el = createTextInput({
      inputAttributes: {
        required: true,
        'aria-hidden': false,
        aria: {label: 'Label', busy: true},
        data: {foo: 'bar', config: {a: 1}},
        class: {active: true, off: false},
      },
    });

    expect(el.hasAttribute('required')).toBe(true);
    expect(el.getAttribute('aria-label')).toBe('Label');
    expect(el.getAttribute('aria-busy')).toBe('');
    expect(el.getAttribute('data-foo')).toBe('bar');
    expect(el.getAttribute('data-config')).toBe('{"a":1}');
    expect(el.classList.contains('active')).toBe(true);
    expect(el.classList.contains('off')).toBe(false);
  });
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
  it('sets the numeric props and posts under its name', () => {
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

    // The element is form-associated, so the name goes on it — there is no
    // hidden input standing in for the value.
    expect(el.name).toBe('cols');
    expect(el.querySelector('input[type="hidden"]')).toBeNull();
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
