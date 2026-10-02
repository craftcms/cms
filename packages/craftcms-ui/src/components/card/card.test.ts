import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftCard from './card.js';
import './card.js';

async function createCard(
  attrs: Record<string, string> = {},
  innerHTML = 'Body'
): Promise<CraftCard> {
  const element = document.createElement('craft-card') as CraftCard;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

function shadow(element: CraftCard, selector: string): HTMLElement | null {
  return element.shadowRoot?.querySelector(selector) ?? null;
}

/**
 * Settle the light-DOM MutationObserver / `slotchange` dispatch and the
 * re-render they queue.
 */
async function settle(element: CraftCard): Promise<void> {
  await new Promise((resolve) => setTimeout(resolve));
  await element.updateComplete;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-card header/footer presence', () => {
  it('renders no header or footer chrome for a bare body', async () => {
    const element = await createCard();

    expect(shadow(element, '.card__header')).toBeNull();
    expect(shadow(element, '.card__footer')).toBeNull();
  });

  it('renders the header for slotted label/actions content', async () => {
    const element = await createCard({}, '<span slot="label">Label</span>Body');
    await settle(element);

    expect(shadow(element, '.card__header')).not.toBeNull();
  });

  it('tracks header and footer content added after first render', async () => {
    const element = await createCard();
    expect(shadow(element, '.card__footer')).toBeNull();

    const footer = document.createElement('div');
    footer.slot = 'footer';
    footer.textContent = 'Meta';
    element.append(footer);
    await settle(element);

    expect(shadow(element, '.card__footer')).not.toBeNull();

    footer.remove();
    await settle(element);

    expect(shadow(element, '.card__footer')).toBeNull();
  });
});

describe('craft-card thumbnail', () => {
  const thumb = '<img slot="thumbnail" src="data:," alt="">Body';

  it('applies the thumbnail grid when thumbnail content is slotted', async () => {
    const element = await createCard({'thumb-alignment': 'end'}, thumb);
    await settle(element);

    expect(shadow(element, '.card--has-thumbnail')).not.toBeNull();
    expect(shadow(element, '.card-body--thumb-end')).not.toBeNull();
  });

  /**
   * A Matrix block's card holds a whole nested form, and a relation field in it
   * renders cards and chips of its own — each carrying `[slot="thumbnail"]`.
   * Those belong to the nested element, not to the block.
   */
  it('ignores a thumbnail slotted into a nested card', async () => {
    const element = await createCard(
      {},
      `<div class="fields"><craft-card>${thumb}</craft-card></div>`
    );
    await settle(element);

    expect(shadow(element, '.card--has-thumbnail')).toBeNull();
    expect(shadow(element, '.card-body__thumb')!.hidden).toBe(true);
  });

  it('ignores a header slotted into a nested card', async () => {
    const element = await createCard(
      {},
      '<div><craft-card><span slot="label">Nested</span>Body</craft-card></div>'
    );
    await settle(element);

    expect(shadow(element, '.card__header')).toBeNull();
  });

  it('suppresses the thumbnail region when show-thumb is false', async () => {
    const element = await createCard({}, thumb);
    await settle(element);
    expect(shadow(element, '.card--has-thumbnail')).not.toBeNull();

    element.showThumb = false;
    await element.updateComplete;

    expect(shadow(element, '.card--has-thumbnail')).toBeNull();
    expect(shadow(element, '.card-body__thumb')!.hidden).toBe(true);
  });
});
