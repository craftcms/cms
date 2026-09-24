import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './tab.js';
import type CraftTab from './tab.js';

async function createTab(
  attrs: Record<string, string> = {},
  innerHTML = 'Content'
): Promise<CraftTab> {
  const element = document.createElement('craft-tab') as CraftTab;
  for (const [name, value] of Object.entries(attrs)) {
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

describe('craft-tab', () => {
  it('renders its label from the default slot', async () => {
    const element = await createTab();

    expect(element.shadowRoot!.querySelector('slot')).toBeTruthy();
    expect(element.textContent).toBe('Content');
  });

  /**
   * The strip reads these off the host, and the stylesheet keys off them, so
   * all three have to reflect rather than stay properties.
   */
  it('reflects disabled', async () => {
    const element = await createTab({disabled: ''});

    expect(element.disabled).toBe(true);
    expect(element.hasAttribute('disabled')).toBe(true);
  });

  it('reflects the panel it controls', async () => {
    const element = await createTab({controls: 'panel-seo'});

    expect(element.controls).toBe('panel-seo');
    expect(element.getAttribute('controls')).toBe('panel-seo');
  });

  it('has no panel to control by default', async () => {
    expect((await createTab()).controls).toBeNull();
  });

  it('follows a controls value set as a property', async () => {
    const element = await createTab();

    element.controls = 'panel-content';
    await element.updateComplete;

    expect(element.getAttribute('controls')).toBe('panel-content');
  });
});
