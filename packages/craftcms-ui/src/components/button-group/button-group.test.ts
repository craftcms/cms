import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftButton from '../button/button.js';
import '../button/button.js';
import './button-group.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

/**
 * The group is a form-associated custom element. happy-dom has no
 * `ElementInternals`, so these run against `element-internals-polyfill`, wired
 * up in the components project's setup.
 */
describe('craft-button-group', () => {
  async function createGroup(multiple: boolean): Promise<{
    form: HTMLFormElement;
    group: HTMLElement;
    buttons: CraftButton[];
  }> {
    const form = document.createElement('form');
    const group = document.createElement('craft-button-group');
    group.name = 'topics';
    group.multiple = multiple;

    const buttons = ['news', 'events'].map((value) => {
      const button = document.createElement('craft-button');
      button.value = value;
      button.textContent = value;
      group.append(button);
      return button;
    });

    form.append(group);
    document.body.append(form);
    await Promise.all([
      group.updateComplete,
      ...buttons.map((button) => button.updateComplete),
    ]);

    return {form, group, buttons};
  }

  const click = (button: CraftButton) =>
    button.dispatchEvent(
      new MouseEvent('click', {bubbles: true, composed: true})
    );

  it('toggles multiple selected values', async () => {
    const {form, group, buttons} = await createGroup(true);

    let values: string[] = [];
    group.addEventListener('change', (event) => {
      values = (event as CustomEvent<{values: string[]}>).detail.values;
    });

    click(buttons[0]!);

    expect(buttons[0]!.active).toBe(true);
    expect(values).toEqual(['news']);
    // Multiple mode posts a PHP-style array, behind an empty sentinel so an
    // empty selection still reaches the server as a key.
    expect(new FormData(form).getAll('topics')).toEqual(['']);
    expect(new FormData(form).getAll('topics[]')).toEqual(['news']);

    click(buttons[1]!);

    expect(values).toEqual(['news', 'events']);
    expect(new FormData(form).getAll('topics[]')).toEqual(['news', 'events']);

    // Clicking a selected button in multiple mode turns it back off.
    click(buttons[0]!);

    expect(buttons[0]!.active).toBe(false);
    expect(values).toEqual(['events']);
  });

  it('posts a single value in radio mode', async () => {
    const {form, group, buttons} = await createGroup(false);

    let value = '';
    group.addEventListener('change', (event) => {
      value = (event as CustomEvent<{value: string}>).detail.value;
    });

    click(buttons[1]!);

    expect(value).toBe('events');
    expect(new FormData(form).get('topics')).toBe('events');
  });
});
