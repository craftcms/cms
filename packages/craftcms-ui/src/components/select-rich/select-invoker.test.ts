import {describe, expect, it} from 'vite-plus/test';

import CraftSelectInvoker from './select-invoker.js';

// Not globally registered: CraftSelectRich wires it in as a scoped element.
customElements.define('test-select-invoker', CraftSelectInvoker);

async function createInvoker(optionHtml: string): Promise<CraftSelectInvoker> {
  const option = document.createElement('div');
  option.innerHTML = optionHtml;

  const invoker = document.createElement(
    'test-select-invoker'
  ) as CraftSelectInvoker;
  invoker.selectedElement = option as never;
  document.body.append(invoker);
  await invoker.updateComplete;

  return invoker;
}

describe('craft-select-rich invoker', () => {
  it('replaces the badge as the selection changes, and as the selected option’s changes', async () => {
    const invoker = await createInvoker(
      'username<span slot="badge">Suggested</span>'
    );
    const next = document.createElement('div');
    next.innerHTML = 'email';

    invoker.selectedElement = next as never;
    await invoker.updateComplete;

    expect(invoker.querySelector('[slot="badge"]')).toBeNull();

    next.insertAdjacentHTML('beforeend', '<span slot="badge">Suggested</span>');
    await new Promise((resolve) => setTimeout(resolve));

    expect(invoker.querySelectorAll('[slot="badge"]')).toHaveLength(1);
  });

  it('appends the selected option’s badge to its value', async () => {
    const invoker = await createInvoker(
      'username<span slot="badge" class="badge">Suggested</span>'
    );
    const wrapper = invoker.shadowRoot!.querySelector('#content-wrapper')!;

    expect(wrapper.querySelector('.value')!.textContent!.trim()).toBe(
      'username'
    );
    expect(wrapper.querySelector('.value + slot[name="badge"]')).toBeTruthy();
    // slotted from the light DOM, so the page's styles reach it
    expect(invoker.querySelector('[slot="badge"].badge')!.textContent).toBe(
      'Suggested'
    );
  });

  it('leaves the badge out when the selected option has none', async () => {
    const invoker = await createInvoker('username');

    expect(invoker.querySelector('[slot="badge"]')).toBeNull();
  });
});
