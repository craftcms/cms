import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './progress-bar.js';
import type CraftProgressBar from './progress-bar.js';

async function createProgressBar(
  attrs: Record<string, string> = {}
): Promise<CraftProgressBar> {
  const element = document.createElement(
    'craft-progress-bar'
  ) as CraftProgressBar;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function track(element: CraftProgressBar): HTMLElement {
  return element.shadowRoot!.querySelector('[part="track"]')!;
}

function fill(element: CraftProgressBar): HTMLElement {
  return element.shadowRoot!.querySelector('[part="fill"]')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-progress-bar', () => {
  it('is a progressbar with the full range declared', async () => {
    const element = await createProgressBar({progress: '40'});

    expect(track(element).getAttribute('role')).toBe('progressbar');
    expect(track(element).getAttribute('aria-valuemin')).toBe('0');
    expect(track(element).getAttribute('aria-valuemax')).toBe('100');
    expect(track(element).getAttribute('aria-valuenow')).toBe('40');
  });

  it('names itself, so a bar on its own is not anonymous', async () => {
    const element = await createProgressBar({label: 'Uploading assets'});

    expect(track(element).getAttribute('aria-label')).toBe('Uploading assets');
  });

  it('fills to the progress value', async () => {
    const element = await createProgressBar({progress: '40'});

    expect(fill(element).style.width).toBe('40%');
  });

  /** A total and a processed count are the other way to express the same thing. */
  it('derives progress from processed and total', async () => {
    const element = await createProgressBar({total: '50', processed: '25'});

    expect(fill(element).style.width).toBe('50%');
  });

  /**
   * A pending bar has no value to report, so it fills the track and drops
   * `aria-valuenow` rather than claiming a number it does not have.
   */
  it('reports no value while pending', async () => {
    const element = await createProgressBar({pending: ''});

    expect(track(element).getAttribute('aria-valuenow')).toBeNull();
    expect(fill(element).style.width).toBe('100%');
    expect(track(element).classList.contains('progress-bar--pending')).toBe(
      true
    );
  });
});
