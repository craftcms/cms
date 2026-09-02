import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './status.js';
import type CraftStatus from './status.js';

async function createStatus(
  attrs: Record<string, string> = {}
): Promise<CraftStatus> {
  const element = document.createElement('craft-status') as CraftStatus;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function dot(element: CraftStatus): HTMLElement {
  return element.shadowRoot!.querySelector('.status')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-status', () => {
  it('renders a dot', async () => {
    const element = await createStatus();

    expect(dot(element)).toBeTruthy();
  });

  it('carries a modifier class for each status', async () => {
    for (const status of [
      'live',
      'pending',
      'expired',
      'disabled',
      'enabled',
    ]) {
      const element = await createStatus({status});

      expect(dot(element).classList.contains(`status--${status}`)).toBe(true);
    }
  });

  /**
   * A dot with nothing to say is decoration beside the label it sits next to.
   * An unnamed `role="img"` would be announced as an image and tell a screen
   * reader user nothing.
   */
  it('is not an image when it has no status and no label', async () => {
    const element = await createStatus();

    expect(dot(element).getAttribute('role')).toBeNull();
    expect(dot(element).getAttribute('aria-label')).toBeNull();
  });

  it('announces the status when one is set', async () => {
    const element = await createStatus({status: 'live'});

    expect(dot(element).getAttribute('role')).toBe('img');
    expect(dot(element).getAttribute('aria-label')).toBe('Status: live');
  });

  it('prefers an explicit label over the status name', async () => {
    const element = await createStatus({status: 'live', label: 'Published'});

    expect(dot(element).getAttribute('aria-label')).toBe('Published');
  });
});
