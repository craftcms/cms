import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './progress.js';
import type CraftProgress from './progress.js';

async function createProgress(
  attrs: Record<string, string> = {}
): Promise<CraftProgress> {
  const element = document.createElement('craft-progress') as CraftProgress;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function canvas(element: CraftProgress): HTMLElement {
  return element.shadowRoot!.querySelector('[part="canvas"]')!;
}

/** The visually hidden text, which is what a screen reader actually gets. */
function announced(element: CraftProgress): string {
  return element
    .shadowRoot!.querySelector('.visually-hidden')!
    .textContent!.trim();
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-progress', () => {
  it('is a progressbar with the full range declared', async () => {
    const element = await createProgress({progress: '60'});

    expect(canvas(element).getAttribute('role')).toBe('progressbar');
    expect(canvas(element).getAttribute('aria-valuemin')).toBe('0');
    expect(canvas(element).getAttribute('aria-valuemax')).toBe('100');
    expect(canvas(element).getAttribute('aria-valuenow')).toBe('60');
  });

  it('names itself', async () => {
    const element = await createProgress({label: 'Generating transforms'});

    expect(canvas(element).getAttribute('aria-label')).toBe(
      'Generating transforms'
    );
  });

  /**
   * The canvas draws the ring, so the state has to be announced in text
   * alongside it — a canvas has nothing a screen reader can read.
   */
  it('announces the percentage in text', async () => {
    expect(announced(await createProgress({progress: '60'}))).toBe('60%');
  });

  /** A negative value means indeterminate rather than a number to report. */
  it('announces loading when there is no value yet', async () => {
    const element = await createProgress({progress: '-1'});

    expect(announced(element)).toBe('Loading');
    expect(canvas(element).getAttribute('aria-valuenow')).toBe('');
  });

  it('announces a failure', async () => {
    expect(announced(await createProgress({progress: '60', failed: ''}))).toBe(
      'Failed'
    );
  });
});
