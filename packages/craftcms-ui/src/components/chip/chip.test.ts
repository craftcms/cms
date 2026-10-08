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

  it('does not render the suffix for an empty suffix element', async () => {
    const element = await createChip({}, 'Label<div slot="suffix"> </div>');

    expect(slot(element, 'suffix')).toBeNull();
  });

  it('ignores slotted content that belongs to a nested chip', async () => {
    const element = await createChip(
      {},
      'Label<span><craft-chip><div slot="suffix">…</div></craft-chip></span>'
    );

    expect(slot(element, 'suffix')).toBeNull();
  });

  it('renders the icon for the icon attribute alone', async () => {
    const element = await createChip({icon: 'star'});

    expect(slot(element, 'icon')).not.toBeNull();
    expect(slot(element, 'prefix')).toBeNull();
  });

  // An empty part would still take the gap between parts.
  it('leaves out slots filled only by an empty wrapper', async () => {
    const element = await createChip(
      {'show-status': '', 'show-thumb': ''},
      '<div slot="status"></div><div slot="thumbnail"> </div>Label<div slot="suffix"></div>'
    );

    expect(slot(element, 'status')).toBeNull();
    expect(slot(element, 'thumbnail')).toBeNull();
    expect(slot(element, 'suffix')).toBeNull();
    expect(element.shadowRoot?.querySelector('.cp-chip__prefix')).toBeNull();
  });

  it('leaves out the label when there is none', async () => {
    const element = await createChip({icon: 'star'}, '');

    expect(element.shadowRoot?.querySelector('.cp-chip__body')).toBeNull();
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
    menu.append(document.createElement('button'));
    element.append(menu);
    await settle(element);

    expect(slot(element, 'suffix')).not.toBeNull();
  });

  it('renders the suffix once an empty suffix element is filled', async () => {
    const element = await createChip({}, 'Label<div slot="suffix"></div>');
    expect(slot(element, 'suffix')).toBeNull();

    element
      .querySelector('[slot="suffix"]')!
      .append(document.createElement('button'));
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
  it('renders a slot when existing content is moved into it', async () => {
    const element = await createChip({}, 'Label<span id="status">Live</span>');
    expect(slot(element, 'status')).toBeNull();

    element.querySelector('#status')!.setAttribute('slot', 'status');
    await settle(element);

    expect(slot(element, 'status')).not.toBeNull();
  });

  it('renders the label once its text arrives', async () => {
    const element = await createChip({}, '');
    const text = document.createTextNode('');
    element.append(text);
    await settle(element);
    expect(element.shadowRoot?.querySelector('.cp-chip__body')).toBeNull();

    text.data = 'Label';
    await settle(element);

    expect(element.shadowRoot?.querySelector('.cp-chip__body')).not.toBeNull();
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

  // A status describes the label, so it sits with it rather than in the prefix.
  it('renders the status beside the label, outside the prefix', async () => {
    const element = await createChip(
      {'show-status': ''},
      '<span slot="status">Live</span>Label'
    );

    expect(
      element.shadowRoot?.querySelector('.cp-chip__main > slot[name="status"]')
    ).not.toBeNull();
    expect(element.shadowRoot?.querySelector('.cp-chip__prefix')).toBeNull();
  });

  // Slotting a status is enough to show it; `show-status` is for a status slot
  // that is filled later, or styled before it is.
  it('renders a slotted status without show-status', async () => {
    const element = await createChip({}, '<span slot="status">Live</span>');

    expect(slot(element, 'status')).not.toBeNull();
  });

  it('leaves the status out when nothing is slotted and show-status is unset', async () => {
    const element = await createChip();

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

  // A host that selects on mousedown would otherwise toggle the item before the
  // checkbox toggles it back.
  it('keeps presses on the checkbox from reaching the host', async () => {
    const element = await createChip({selectable: ''});
    const reached: string[] = [];
    for (const type of ['mousedown', 'mouseup']) {
      element.addEventListener(type, () => reached.push(type));
      checkbox(element)!.dispatchEvent(
        new MouseEvent(type, {bubbles: true, composed: true})
      );
    }

    expect(reached).toEqual([]);
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

  it('emits craft-selection-change with the new state', async () => {
    const element = await createChip({selectable: ''});
    const events: Array<CustomEvent> = [];
    element.addEventListener('craft-selection-change', (event) =>
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
    element.addEventListener('craft-selection-change', (event) =>
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

describe('craft-chip buttons', () => {
  it('has its buttons inherit the chip palette', async () => {
    const element = await createChip(
      {},
      'Label<div slot="suffix"><craft-button>Edit</craft-button></div>'
    );
    await settle(element);

    expect(element.querySelector('craft-button')?.hasAttribute('inherit')).toBe(
      true
    );
  });

  it('has buttons added after mount inherit too', async () => {
    const element = await createChip({}, 'Label<div slot="suffix"></div>');

    element
      .querySelector('[slot="suffix"]')!
      .append(document.createElement('craft-button'));
    await settle(element);

    expect(element.querySelector('craft-button')?.hasAttribute('inherit')).toBe(
      true
    );
  });
});
