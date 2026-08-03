import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {createButton, createPasteButton, createSubmitButton} from './create.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('createButton', () => {
  it('creates a craft-button with a slotted label span', () => {
    const button = createButton({label: 'Save'});

    expect(button.tagName.toLowerCase()).toBe('craft-button');
    expect(button.type).toBe('button');
    const label = button.querySelector('.label');
    expect(label).not.toBeNull();
    expect(label!.textContent).toBe('Save');
  });

  it('applies the legacy config keys', () => {
    const button = createButton({
      id: 'my-btn',
      class: 'foo bar',
      ariaLabel: 'The button',
      ariaDescribedBy: 'desc',
      role: 'option',
      toggle: true,
      controls: 'menu-1',
      data: {key: 'entry', count: 2},
      icon: 'plus',
      disabled: true,
    });

    expect(button.id).toBe('my-btn');
    expect(button.classList.contains('foo')).toBe(true);
    expect(button.classList.contains('bar')).toBe(true);
    expect(button.getAttribute('aria-label')).toBe('The button');
    expect(button.getAttribute('aria-describedby')).toBe('desc');
    expect(button.getAttribute('role')).toBe('option');
    expect(button.getAttribute('aria-expanded')).toBe('false');
    expect(button.getAttribute('aria-controls')).toBe('menu-1');
    expect(button.getAttribute('data-key')).toBe('entry');
    expect(button.getAttribute('data-count')).toBe('2');
    expect(button.icon).toBe('plus');
    expect(button.disabled).toBe(true);
  });

  it('renders an html label when no plain label is given', () => {
    const button = createButton({html: '<em>Fancy</em>'});

    expect(button.querySelector('.label em')).not.toBeNull();
  });

  it('supports the modern options', () => {
    const button = createButton({
      variant: 'danger',
      size: 'small',
      loading: true,
    });

    expect(button.variant).toBe('danger');
    expect(button.size).toBe('small');
    expect(button.loading).toBe(true);
  });
});

describe('createSubmitButton', () => {
  it('creates an accent submit button with the legacy class token', () => {
    const button = createSubmitButton();

    expect(button.type).toBe('submit');
    expect(button.variant).toBe('accent');
    expect(button.classList.contains('submit')).toBe(true);
    expect(button.querySelector('.label')!.textContent).toBe('Submit');
  });

  it('keeps a configured label and variant', () => {
    const button = createSubmitButton({label: 'Apply', variant: 'neutral'});

    expect(button.querySelector('.label')!.textContent).toBe('Apply');
    expect(button.variant).toBe('neutral');
  });
});

describe('createPasteButton', () => {
  it('creates a paste button with icon, default label, and class token', () => {
    const button = createPasteButton();

    expect(button.icon).toBe('duplicate');
    expect(button.classList.contains('paste-btn')).toBe(true);
    expect(button.querySelector('.label')!.textContent).toBe('Paste elements');
  });
});
