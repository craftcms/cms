import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftChip from './chip.js';
import './chip.js';

async function createChip(
  attrs: Record<string, string> = {},
  innerHTML = 'Label'
): Promise<CraftChip> {
  const element = document.createElement('craft-chip') as CraftChip;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;

  return element;
}

function slot(element: CraftChip, name: string): HTMLElement | null {
  return element.shadowRoot?.querySelector(`slot[name="${name}"]`) ?? null;
}

/** Settle the MutationObserver callback and the re-render it queues. */
async function settle(element: CraftChip): Promise<void> {
  await new Promise((resolve) => setTimeout(resolve));
  await element.updateComplete;
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-chip slots', () => {
  it('renders neither prefix nor suffix for a bare label', async () => {
    const element = await createChip();

    expect(slot(element, 'prefix')).toBeNull();
    expect(slot(element, 'suffix')).toBeNull();
  });

  it('renders the suffix for slotted content', async () => {
    const element = await createChip({}, 'Label<div slot="suffix">…</div>');

    expect(slot(element, 'suffix')).not.toBeNull();
  });

  it('renders the prefix for the icon attribute alone', async () => {
    const element = await createChip({icon: 'star'});

    expect(slot(element, 'prefix')).not.toBeNull();
  });
});

/**
 * Chips are routinely filled in after they mount — `Craft.addActionsToChip()`
 * injects an action menu into `[slot="suffix"]` once it has the element's
 * actions. A one-shot slot check would leave that menu invisible.
 */
describe('craft-chip light DOM changes', () => {
  it('renders the suffix for content injected after mount', async () => {
    const element = await createChip();
    expect(slot(element, 'suffix')).toBeNull();

    const menu = document.createElement('div');
    menu.slot = 'suffix';
    element.append(menu);
    await settle(element);

    expect(slot(element, 'suffix')).not.toBeNull();
  });

  it('drops the suffix again when its content is removed', async () => {
    const element = await createChip({}, 'Label<div slot="suffix">…</div>');
    expect(slot(element, 'suffix')).not.toBeNull();

    element.querySelector('[slot="suffix"]')!.remove();
    await settle(element);

    expect(slot(element, 'suffix')).toBeNull();
  });

  /** Content is moved into a slot by setting the attribute, not only by being appended. */
  it('renders the prefix when existing content is moved into the slot', async () => {
    const element = await createChip({}, 'Label<div id="thumb"></div>');
    expect(slot(element, 'prefix')).toBeNull();

    element.querySelector('#thumb')!.setAttribute('slot', 'thumbnail');
    await settle(element);

    expect(slot(element, 'prefix')).not.toBeNull();
  });
});
