import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './info-icon.js';
import type CraftInfoIcon from './info-icon.js';

async function createInfoIcon(
  attrs: Record<string, string> = {},
  innerHTML = 'Shown in the entry index.'
): Promise<CraftInfoIcon> {
  const element = document.createElement('craft-info-icon') as CraftInfoIcon;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function icon(element: CraftInfoIcon): Element {
  return element.shadowRoot!.querySelector('craft-icon')!;
}

function tooltip(element: CraftInfoIcon): Element {
  return element.shadowRoot!.querySelector('craft-tooltip')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-info-icon', () => {
  it('renders a button carrying the icon', async () => {
    const element = await createInfoIcon();

    expect(element.shadowRoot!.querySelector('craft-button')).toBeTruthy();
    expect(icon(element).getAttribute('name')).toBe('circle-info');
  });

  it('takes an icon of its own', async () => {
    const element = await createInfoIcon({icon: 'triangle-exclamation'});

    expect(icon(element).getAttribute('name')).toBe('triangle-exclamation');
  });

  /**
   * The button holds only an icon, so the name has to come from the icon's
   * label — without it the control is announced as an unnamed button.
   */
  it('names the icon-only button', async () => {
    const element = await createInfoIcon();

    expect(icon(element).getAttribute('label')).toBeTruthy();
  });

  it('takes a label of its own', async () => {
    const element = await createInfoIcon({label: 'What is this?'});

    expect(icon(element).getAttribute('label')).toBe('What is this?');
  });

  /** The tooltip has to point at the button that opens it. */
  it('wires the tooltip to the button', async () => {
    const element = await createInfoIcon();
    const button = element.shadowRoot!.querySelector('craft-button')!;

    expect(element.id).not.toBe('');
    expect(button.id).toBe(element.id);
    expect(tooltip(element).getAttribute('for')).toBe(element.id);
  });

  it('holds its content in the tooltip', async () => {
    const element = await createInfoIcon();

    expect(tooltip(element).querySelector('slot')).toBeTruthy();
    expect(element.textContent).toBe('Shown in the entry index.');
  });
});
