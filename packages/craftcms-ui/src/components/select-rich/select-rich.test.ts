import {beforeEach, describe, expect, it} from 'vite-plus/test';

import {html} from 'lit';
import CraftSelectRich from './select-rich.js';
import type CraftOption from '../option/option.js';

class TestSelectRich extends CraftSelectRich {
  protected override _selectedTemplate(option: CraftOption | undefined) {
    return html`<b>${option?.value ?? 'none'}</b>`;
  }
}
customElements.define('test-select-rich', TestSelectRich);

async function createSelectRich(
  attrs: Record<string, string> = {}
): Promise<CraftSelectRich> {
  const element = document.createElement(
    'craft-select-rich'
  ) as CraftSelectRich;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = '<label slot="label">Favorite Fruit</label>';
  document.body.append(element);
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-select-rich', () => {
  it('leaves keyboard shortcuts out of type-ahead', async () => {
    const element = await createSelectRich();
    element.insertAdjacentHTML(
      'beforeend',
      '<craft-option value="apple">Apple</craft-option>' +
        '<craft-option value="strawberry">Strawberry</craft-option>'
    );
    await new Promise((resolve) => setTimeout(resolve));
    const invoker = element.querySelector('craft-select-invoker')!;
    const press = (init: KeyboardEventInit) =>
      invoker.dispatchEvent(
        new KeyboardEvent('keydown', {bubbles: true, ...init})
      );

    // ⌘S while the select has focus must not jump to an "S" option.
    press({key: 's', metaKey: true});
    press({key: 's', ctrlKey: true});
    expect(element.value).toBe('apple');

    press({key: 's'});
    expect(element.value).toBe('strawberry');
  });

  it('lets a subclass choose what the field shows for the selection', async () => {
    const element = document.createElement('test-select-rich');
    element.innerHTML =
      '<craft-option value="apple">Apple</craft-option>' +
      '<craft-option value="pear" selected>Pear</craft-option>';
    document.body.append(element);
    await new Promise((resolve) => setTimeout(resolve));

    expect(element.querySelector('craft-select-invoker')?.textContent).toBe(
      'pear'
    );
  });
});

describe('craft-select-rich badge', () => {
  const tick = () => new Promise((resolve) => setTimeout(resolve));

  async function createBadged(): Promise<CraftSelectRich> {
    const element = await createSelectRich();
    element.insertAdjacentHTML(
      'beforeend',
      '<craft-option value="username">username<span slot="badge" class="badge">Suggested</span></craft-option>' +
        '<craft-option value="email">email</craft-option>'
    );
    await tick();
    return element;
  }

  const badges = (element: CraftSelectRich) =>
    [...element.querySelectorAll('craft-select-invoker > [slot="badge"]')].map(
      (badge) => badge.textContent
    );

  it('appends the selected option’s badge to its value', async () => {
    const element = await createBadged();
    const invoker = element.querySelector('craft-select-invoker')!;
    const wrapper = invoker.shadowRoot!.querySelector('#content-wrapper')!;

    expect(wrapper.querySelector('.value + slot[name="badge"]')).toBeTruthy();
    // slotted from the light DOM, so the page's styles reach it
    expect(invoker.querySelector(':scope > [slot="badge"].badge')).toBeTruthy();
    expect(badges(element)).toEqual(['Suggested']);
  });

  it('replaces the badge as the selection changes, and as the selected option’s changes', async () => {
    const element = await createBadged();

    element.value = 'email';
    await tick();

    expect(badges(element)).toEqual([]);

    element
      .querySelector('craft-option[value="email"]')!
      .insertAdjacentHTML('beforeend', '<span slot="badge">Suggested</span>');
    await tick();

    expect(badges(element)).toEqual(['Suggested']);
  });

  it('follows the selected option’s badge after being moved, and as its text changes', async () => {
    const element = await createBadged();
    const option = element.querySelector('craft-option[value="username"]')!;
    const elsewhere = document.createElement('div');
    document.body.append(elsewhere);

    // a keyed reorder moves the select with the same option still selected
    elsewhere.append(element);
    option.insertAdjacentHTML('beforeend', '<span slot="badge">New</span>');
    await tick();

    expect(badges(element)).toEqual(['Suggested', 'New']);

    option.querySelector('[slot="badge"]')!.firstChild!.textContent = 'Picked';
    await tick();

    expect(badges(element)[0]).toBe('Picked');
  });
});
