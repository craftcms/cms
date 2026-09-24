import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './avatar.js';
import type CraftAvatar from './avatar.js';

async function createAvatar(
  attrs: Record<string, string> = {},
  innerHTML = 'BH'
): Promise<CraftAvatar> {
  const element = document.createElement('craft-avatar') as CraftAvatar;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function svg(element: CraftAvatar): SVGElement {
  return element.shadowRoot!.querySelector('svg')!;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-avatar', () => {
  it('renders the gradient artwork', async () => {
    const element = await createAvatar();

    expect(svg(element)).toBeTruthy();
    expect(element.shadowRoot!.querySelector('linearGradient')).toBeTruthy();
  });

  /**
   * An avatar that names nothing is decoration beside the name it sits next to,
   * so it is hidden rather than announced as an unnamed image.
   */
  it('is hidden from assistive technology without a label', async () => {
    const element = await createAvatar();

    expect(svg(element).getAttribute('aria-hidden')).toBe('true');
    expect(svg(element).getAttribute('role')).toBeNull();
    expect(element.shadowRoot!.querySelector('title')).toBeNull();
  });

  it('becomes a named image once it has a label', async () => {
    const element = await createAvatar({label: 'Brian Hanson'});

    expect(svg(element).getAttribute('role')).toBe('img');
    expect(svg(element).getAttribute('aria-hidden')).toBeNull();
    expect(element.shadowRoot!.querySelector('title')?.textContent).toBe(
      'Brian Hanson'
    );
  });

  it('gives each instance its own gradient id, so they do not collide', async () => {
    const first = await createAvatar();
    const second = await createAvatar();

    const id = (element: CraftAvatar) =>
      element.shadowRoot!.querySelector('linearGradient')!.id;

    expect(id(first)).not.toBe('');
    expect(id(first)).not.toBe(id(second));
  });
});
