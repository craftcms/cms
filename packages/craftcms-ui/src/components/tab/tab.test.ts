import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './tab.js';
import type CraftTab from './tab.js';

async function createTab(innerHTML = 'Content'): Promise<CraftTab> {
  const element = document.createElement('craft-tab') as CraftTab;
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-tab', () => {
  it('mirrors its label text for the width reservation', async () => {
    const element = await createTab();
    const label = () =>
      element
        .shadowRoot!.querySelector('.tab__label')!
        .getAttribute('data-text');

    expect(label()).toBe('Content');

    element.firstChild!.textContent = 'Settings';
    await new Promise((resolve) => setTimeout(resolve));
    await element.updateComplete;

    expect(label()).toBe('Settings');
  });

  /**
   * The strip reads these off the host, and the stylesheet keys off them, so
   * all three have to reflect rather than stay properties.
   */
  it('only shows a pointer when it can be selected', async () => {
    const element = await createTab();

    expect(getComputedStyle(element).cursor).toBe('pointer');

    element.setAttribute('selected', '');
    expect(getComputedStyle(element).cursor).toBe('default');
  });

  it('follows a controls value set as a property', async () => {
    const element = await createTab();

    element.controls = 'panel-content';
    await element.updateComplete;

    expect(element.getAttribute('controls')).toBe('panel-content');
  });
});

describe('craft-tab suffix', () => {
  it('slots suffix content after the label', async () => {
    document.body.innerHTML = `
      <craft-tab>Content<craft-icon slot="suffix" name="circle-exclamation" label="Errors"></craft-icon></craft-tab>`;
    const tab = document.querySelector<CraftTab>('craft-tab')!;
    await tab.updateComplete;

    const suffix = tab.shadowRoot!.querySelector<HTMLSlotElement>(
      'slot[name="suffix"]'
    )!;

    expect(suffix.assignedElements()).toEqual([
      tab.querySelector('craft-icon'),
    ]);
    expect(
      suffix.compareDocumentPosition(
        tab.shadowRoot!.querySelector('.tab__label')!
      ) & Node.DOCUMENT_POSITION_PRECEDING
    ).toBeTruthy();
  });
});

describe('craft-tab prefix', () => {
  it('slots prefix content before the label', async () => {
    document.body.innerHTML = `
      <craft-tab><craft-icon slot="prefix" name="newspaper"></craft-icon>Content</craft-tab>`;
    const tab = document.querySelector<CraftTab>('craft-tab')!;
    await tab.updateComplete;

    const prefix = tab.shadowRoot!.querySelector<HTMLSlotElement>(
      'slot[name="prefix"]'
    )!;

    expect(prefix.assignedElements()).toEqual([
      tab.querySelector('craft-icon'),
    ]);
    expect(
      prefix.compareDocumentPosition(
        tab.shadowRoot!.querySelector('.tab__label')!
      ) & Node.DOCUMENT_POSITION_FOLLOWING
    ).toBeTruthy();
  });
});
