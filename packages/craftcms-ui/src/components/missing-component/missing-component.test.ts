import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftMissingComponent from './missing-component.js';
import './missing-component.js';

async function createMissingComponent(
  attributes: Record<string, string>,
  innerHTML = ''
): Promise<CraftMissingComponent> {
  const element = document.createElement(
    'craft-missing-component'
  ) as CraftMissingComponent;

  for (const [name, value] of Object.entries(attributes)) {
    element.setAttribute(name, value);
  }

  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;

  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-missing-component', () => {
  it('renders the missing component error', async () => {
    const element = await createMissingComponent({
      error: 'Unable to find Acme\\Missing.',
    });

    expect(
      element.shadowRoot?.querySelector('[role="alert"]')?.textContent
    ).toBe('Unable to find Acme\\Missing.');
    expect(element.shadowRoot?.querySelector('.install-plugin')).toBeNull();
  });

  it('renders plugin details and slotted actions', async () => {
    const element = await createMissingComponent(
      {error: 'Plugin disabled.', 'plugin-name': 'Example'},
      '<img slot="icon" src="data:," alt=""><button slot="action">Enable</button>'
    );

    expect(element.shadowRoot?.querySelector('h3')?.textContent).toBe(
      'Example'
    );
    expect(element.querySelector('[slot="icon"]')).not.toBeNull();
    expect(element.querySelector('[slot="action"]')?.textContent).toBe(
      'Enable'
    );
  });
});
