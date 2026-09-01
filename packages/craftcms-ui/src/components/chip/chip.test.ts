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

describe('craft-chip status', () => {
  it('renders the status slot when show-status is set', async () => {
    const element = await createChip(
      {'show-status': ''},
      '<span slot="status">Live</span>'
    );

    expect(slot(element, 'status')).not.toBeNull();
  });

  // A status is prefix content like any other; without it counting, a chip whose
  // only prefix content is its status would render no prefix at all.
  it('renders the prefix for a status alone', async () => {
    const element = await createChip(
      {'show-status': ''},
      '<span slot="status">Live</span>'
    );

    expect(element.shadowRoot?.querySelector('[part="prefix"]')).not.toBeNull();
  });

  it('leaves the status out until show-status is set', async () => {
    const element = await createChip({}, '<span slot="status">Live</span>');

    expect(slot(element, 'status')).toBeNull();
  });
});

describe('craft-chip selection', () => {
  function checkbox(element: CraftChip): HTMLInputElement | null {
    return element.shadowRoot?.querySelector('input[type="checkbox"]') ?? null;
  }

  it('offers no checkbox unless selectable', async () => {
    expect(checkbox(await createChip())).toBeNull();
  });

  it('reflects `selected` onto the checkbox', async () => {
    const element = await createChip({selectable: '', selected: ''});

    expect(checkbox(element)?.checked).toBe(true);
  });

  it('labels the checkbox from select-label', async () => {
    const element = await createChip({
      selectable: '',
      'select-label': 'Select Homepage',
    });

    expect(checkbox(element)?.getAttribute('aria-label')).toBe(
      'Select Homepage'
    );
  });

  it('emits selected-change with the new state', async () => {
    const element = await createChip({selectable: ''});
    const events: Array<CustomEvent> = [];
    element.addEventListener('selected-change', (event) =>
      events.push(event as CustomEvent)
    );

    checkbox(element)!.click();
    await element.updateComplete;

    expect(events).toHaveLength(1);
    expect(events[0]!.detail).toEqual({selected: true, shiftKey: false});
    expect(element.selected).toBe(true);
  });

  // `change` carries no modifier keys, so the preceding click is where a
  // shift-range has to be read from.
  it('carries the shift key from the click that preceded the change', async () => {
    const element = await createChip({selectable: ''});
    const events: Array<CustomEvent> = [];
    element.addEventListener('selected-change', (event) =>
      events.push(event as CustomEvent)
    );

    const input = checkbox(element)!;
    input.dispatchEvent(new MouseEvent('click', {shiftKey: true}));
    input.checked = true;
    input.dispatchEvent(new Event('change'));
    await element.updateComplete;

    expect(events[0]!.detail).toEqual({selected: true, shiftKey: true});
  });

  // Otherwise every host would have to filter the checkbox back out of its own
  // click handler.
  it('keeps a checkbox click from reading as a click on the chip', async () => {
    const element = await createChip({selectable: ''});
    let chipClicks = 0;
    element.addEventListener('click', () => chipClicks++);

    checkbox(element)!.click();
    await element.updateComplete;

    expect(chipClicks).toBe(0);
  });
});
