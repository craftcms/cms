import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './disclosure.js';
import type CraftDisclosure from './disclosure.js';

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
  it('honours a collapsed state set on the host', async () => {
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
    element.addEventListener('open', () => seen.push('open'));
    element.addEventListener('close', () => seen.push('close'));

    trigger(element).click();
    await element.updateComplete;
    trigger(element).click();
    await element.updateComplete;

    expect(seen).toEqual(['close', 'open']);
  });
});
