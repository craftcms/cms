import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './disclosure.js';
import type CraftDisclosure from './disclosure.js';
import type CraftButton from '../button/button.js';

/**
 * External-target mode: a bare `button[aria-controls]` inside the disclosure,
 * pointing at an element elsewhere in the document. This is the contract of the
 * legacy `CraftDisclosure` element.
 */
async function createExternal(
  attrs: Record<string, string> = {}
): Promise<{element: CraftDisclosure; target: HTMLElement}> {
  const target = document.createElement('div');
  target.id = 'target';
  target.dataset.state = 'collapsed';

  const element = document.createElement('craft-disclosure') as CraftDisclosure;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML =
    '<button type="button" aria-controls="target">Toggle</button>';

  // The target has to be in the document before the disclosure connects: the
  // component looks it up by id while setting external mode up.
  document.body.append(target);
  document.body.append(element);
  await element.updateComplete;
  await new Promise((resolve) => setTimeout(resolve, 0));
  await element.updateComplete;
  return {element, target};
}

function trigger(element: CraftDisclosure): HTMLButtonElement {
  return element.querySelector('button')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-disclosure', () => {
  /**
   * With no `state` in the markup the disclosure opens, whatever the target's
   * own `data-state` said — the component owns the state rather than adopting
   * what it finds.
   */
  it('defaults to expanded', async () => {
    const {element, target} = await createExternal();

    expect(element.state).toBe('expanded');
    expect(target.dataset.state).toBe('expanded');
    expect(trigger(element).getAttribute('aria-expanded')).toBe('true');
  });

  /** `state` is reflected, so a consumer can start it closed from markup. */
  it('honors a collapsed state set on the host', async () => {
    const {element, target} = await createExternal({state: 'collapsed'});

    expect(target.dataset.state).toBe('collapsed');
    expect(trigger(element).getAttribute('aria-expanded')).toBe('false');
  });

  /** The consumer supplies the CSS; the component only flips `data-state`. */
  it('collapses the target it controls', async () => {
    const {element, target} = await createExternal();

    trigger(element).click();
    await element.updateComplete;

    expect(target.dataset.state).toBe('collapsed');
    expect(trigger(element).getAttribute('aria-expanded')).toBe('false');
  });

  it('expands again', async () => {
    const {element, target} = await createExternal();

    trigger(element).click();
    await element.updateComplete;
    trigger(element).click();
    await element.updateComplete;

    expect(target.dataset.state).toBe('expanded');
    expect(trigger(element).getAttribute('aria-expanded')).toBe('true');
  });

  it('fires close and open as it toggles', async () => {
    const {element} = await createExternal();
    const seen: string[] = [];
    element.addEventListener('craft-show', () => seen.push('open'));
    element.addEventListener('craft-hide', () => seen.push('close'));

    trigger(element).click();
    await element.updateComplete;
    trigger(element).click();
    await element.updateComplete;

    expect(seen).toEqual(['close', 'open']);
  });
});

describe('craft-disclosure invoker', () => {
  async function createSlotted(
    invokerHtml?: string
  ): Promise<{element: CraftDisclosure; invoker: CraftButton}> {
    const element = document.createElement(
      'craft-disclosure'
    ) as CraftDisclosure;
    element.setAttribute('label', 'Advanced');
    element.innerHTML = `${invokerHtml ?? ''}<div slot="content">Settings</div>`;
    document.body.append(element);
    await element.updateComplete;

    return {
      element,
      invoker: element.querySelector<CraftButton>('[slot="invoker"]')!,
    };
  }

  it('renders a plain, flush default invoker', async () => {
    const {invoker} = await createSlotted();

    expect(invoker.localName).toBe('craft-button');
    expect(invoker.getAttribute('variant')).toBe('plain');
    expect(invoker.getAttribute('flush')).toBe('');
    expect(invoker.textContent).toBe('Advanced');
  });

  it('sizes the default invoker to match', async () => {
    const {element, invoker} = await createSlotted();
    expect(invoker.getAttribute('size')).toBe('medium');

    element.size = 'small';
    await element.updateComplete;

    expect(invoker.getAttribute('size')).toBe('small');
  });

  it.each([
    ['default', undefined],
    [
      'slotted',
      '<craft-button slot="invoker" type="button" variant="plain" icon="chevron-right" icon-position="suffix">More</craft-button>',
    ],
    [
      'down-chevron slotted',
      '<craft-button slot="invoker" type="button" variant="plain" icon="chevron-down">More</craft-button>',
    ],
  ])(
    'points a %s invoker’s trailing chevron right, then down while open',
    async (_, html) => {
      const {element, invoker} = await createSlotted(html);
      expect(invoker.iconPosition).toBe('suffix');
      expect(invoker.icon).toBe('chevron-right');

      element.opened = true;
      await element.updateComplete;
      expect(invoker.icon).toBe('chevron-down');

      element.opened = false;
      await element.updateComplete;
      expect(invoker.icon).toBe('chevron-right');
    }
  );

  // Vue binds `icon` as a property, so there's no attribute to read.
  it('points the chevron down when the icon was set as a property', async () => {
    const element = document.createElement(
      'craft-disclosure'
    ) as CraftDisclosure;
    const invoker = document.createElement('craft-button');
    invoker.slot = 'invoker';
    invoker.icon = 'chevron-right';
    element.append(invoker);
    element.opened = true;
    document.body.append(element);
    await element.updateComplete;

    expect(invoker.hasAttribute('icon')).toBe(false);
    expect(invoker.icon).toBe('chevron-down');
  });

  it('leaves an invoker’s other icons alone', async () => {
    const {element, invoker} = await createSlotted(
      '<craft-button slot="invoker" type="button" icon="gear">Settings</craft-button>'
    );

    element.opened = true;
    await element.updateComplete;

    expect(invoker.icon).toBe('gear');
  });
});
