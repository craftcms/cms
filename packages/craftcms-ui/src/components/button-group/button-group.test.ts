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

  it('adopts its value from a child marked active in markup', async () => {
    // A group given its selection in markup used to have it stripped: with no
    // `value`, the first sync cleared `active` from every child.
    document.body.innerHTML = `
      <craft-button-group name="orientation">
        <craft-button value="landscape" active></craft-button>
        <craft-button value="portrait"></craft-button>
      </craft-button-group>
    `;

    const group = document.querySelector('craft-button-group')!;
    await group.updateComplete;

    const [landscape, portrait] =
      document.querySelectorAll<CraftButton>('craft-button');

    expect(group.value).toBe('landscape');
    expect(landscape.hasAttribute('active')).toBe(true);
    expect(landscape.getAttribute('aria-pressed')).toBe('true');
    expect(portrait.hasAttribute('active')).toBe(false);
  });

  it('lets an explicit value win over a child marked active', async () => {
    document.body.innerHTML = `
      <craft-button-group name="orientation" value="portrait">
        <craft-button value="landscape" active></craft-button>
        <craft-button value="portrait"></craft-button>
      </craft-button-group>
    `;

    const group = document.querySelector('craft-button-group')!;
    await group.updateComplete;

    const [landscape, portrait] =
      document.querySelectorAll<CraftButton>('craft-button');

    expect(group.value).toBe('portrait');
    expect(landscape.hasAttribute('active')).toBe(false);
    expect(portrait.hasAttribute('active')).toBe(true);
  });

  it('adopts only once, so a later sync cannot resurrect the old value', async () => {
    document.body.innerHTML = `
      <craft-button-group name="orientation">
        <craft-button value="landscape" active></craft-button>
        <craft-button value="portrait"></craft-button>
      </craft-button-group>
    `;

    const group = document.querySelector('craft-button-group')!;
    await group.updateComplete;

    group.value = 'portrait';
    await group.updateComplete;

    const [landscape, portrait] =
      document.querySelectorAll<CraftButton>('craft-button');

    expect(group.value).toBe('portrait');
    expect(landscape.hasAttribute('active')).toBe(false);
    expect(portrait.hasAttribute('active')).toBe(true);
  });

  it('leaves multi-select alone, which already reads active off its children', async () => {
    document.body.innerHTML = `
      <craft-button-group name="topics" multiple>
        <craft-button value="news" active></craft-button>
        <craft-button value="sport" active></craft-button>
      </craft-button-group>
    `;

    const group = document.querySelector('craft-button-group')!;
    await group.updateComplete;

    const buttons = document.querySelectorAll<CraftButton>('craft-button');

    expect(group.value).toBeUndefined();
    expect([...buttons].every((b) => b.hasAttribute('active'))).toBe(true);
  });
});
