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

  it('adopts its value from a child marked active in markup', async () => {
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
    expect(landscape!.hasAttribute('active')).toBe(true);
    expect(landscape!.getAttribute('aria-pressed')).toBe('true');
    expect(portrait!.hasAttribute('active')).toBe(false);
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
    expect(landscape!.hasAttribute('active')).toBe(false);
    expect(portrait!.hasAttribute('active')).toBe(true);
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
    expect(landscape!.hasAttribute('active')).toBe(false);
    expect(portrait!.hasAttribute('active')).toBe(true);
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
