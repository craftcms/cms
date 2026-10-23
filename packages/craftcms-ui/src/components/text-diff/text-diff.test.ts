import {beforeEach, describe, expect, it} from 'vite-plus/test';

import './text-diff.js';
import type CraftTextDiff from './text-diff.js';

const filler = 'Filler words pad out this paragraph nicely. '.repeat(10);

async function createDiff(
  properties: Partial<
    Pick<
      CraftTextDiff,
      'current' | 'proposed' | 'highlights' | 'collapse' | 'label'
    >
  >
): Promise<CraftTextDiff> {
  const element = document.createElement('craft-text-diff');
  Object.assign(element, properties);
  document.body.append(element);
  await element.updateComplete;

  return element;
}

const shadow = (element: CraftTextDiff) => element.shadowRoot!;
const toggle = (element: CraftTextDiff) =>
  shadow(element).querySelector<HTMLElement>('craft-button');

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-text-diff', () => {
  it('marks deleted and inserted text, naming each for screen readers', async () => {
    const element = await createDiff({
      current: 'A kitten was tucked beneath a basket.',
      proposed: 'A kitten was shivering beneath a basket.',
    });

    expect(shadow(element).querySelector('del')!.textContent).toBe(
      'Deleted: tucked'
    );
    expect(shadow(element).querySelector('ins')!.textContent).toBe(
      'Inserted: shivering'
    );
    expect(
      shadow(element).querySelector('del .cp-visually-hidden')!.textContent
    ).toBe('Deleted: ');
  });

  it('marks highlighted passages', async () => {
    const element = await createDiff({
      current: 'The kitten was tucked beneath a basket, shivering.',
      proposed: 'The kitten was shivering beneath a basket.',
      highlights: ['tucked'],
    });

    expect(shadow(element).querySelector('del mark')!.textContent).toBe(
      'tucked'
    );
  });

  it('names collapsed stretches for screen readers', async () => {
    const element = await createDiff({
      current: `Old start. ${filler}`,
      proposed: `New start. ${filler}`,
      collapse: true,
    });
    const gap = shadow(element).querySelector('.gap')!;

    expect(gap.querySelector('[aria-hidden="true"]')!.textContent).toBe('…');
    expect(gap.querySelector('.cp-visually-hidden')!.textContent).toBe(
      'Unchanged text'
    );
  });

  it('shows all of the collapsed text, and only the changes again, from its button', async () => {
    const element = await createDiff({
      current: `Old start. ${filler}`,
      proposed: `New start. ${filler}`,
      collapse: true,
      label: 'Body',
    });
    const button = toggle(element)!;

    expect(button.getAttribute('aria-controls')).toBe('text');
    expect(shadow(element).getElementById('text')).not.toBeNull();
    expect(button.getAttribute('aria-expanded')).toBe('false');
    expect(button.textContent!.replace(/\s+/g, ' ').trim()).toBe(
      'Show full text Body'
    );

    button.click();
    await element.updateComplete;

    expect(button.getAttribute('aria-expanded')).toBe('true');
    expect(shadow(element).querySelector('.gap')).toBeNull();
    expect(button.textContent!.replace(/\s+/g, ' ').trim()).toBe(
      'Show changes only Body'
    );

    button.click();
    await element.updateComplete;

    expect(button.getAttribute('aria-expanded')).toBe('false');
    expect(shadow(element).querySelector('.gap')).not.toBeNull();
  });

  it('collapses again when its texts change', async () => {
    const element = await createDiff({
      current: `Old start. ${filler}`,
      proposed: `New start. ${filler}`,
      collapse: true,
    });

    toggle(element)!.click();
    await element.updateComplete;
    element.proposed = `Newer start. ${filler}`;
    await element.updateComplete;

    expect(toggle(element)!.getAttribute('aria-expanded')).toBe('false');
    expect(shadow(element).querySelector('.gap')).not.toBeNull();
  });

  it('only shows its button when collapsing hides text', async () => {
    const short = await createDiff({
      current: 'Old start.',
      proposed: 'New start.',
      collapse: true,
    });
    const notCollapsed = await createDiff({
      current: `Old start. ${filler}`,
      proposed: `New start. ${filler}`,
    });

    expect(toggle(short)).toBeNull();
    expect(toggle(notCollapsed)).toBeNull();
    expect(shadow(notCollapsed).querySelector('.gap')).toBeNull();
  });

  it('adds no whitespace of its own around the text', async () => {
    const element = await createDiff({
      current: 'A kitten was shivering.',
      proposed: 'A kitten was shivering.',
    });

    expect(shadow(element).getElementById('text')!.textContent).toBe(
      'A kitten was shivering.'
    );
  });
});
