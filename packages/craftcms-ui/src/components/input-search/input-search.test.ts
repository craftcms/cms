import {beforeEach, describe, expect, it} from 'vitest';
import type CraftInputSearch from './input-search.js';
import './input-search.js';

async function createInputSearch(
  markup = '<craft-input-search></craft-input-search>'
): Promise<CraftInputSearch> {
  document.body.innerHTML = markup;
  const element = document.querySelector('craft-input-search')!;
  await element.updateComplete;
  return element;
}

async function type(element: CraftInputSearch, value: string) {
  const input = element.querySelector('input')!;
  input.value = value;
  input.dispatchEvent(new Event('input', {bubbles: true}));
  await element.updateComplete;
}

function clearButton(element: CraftInputSearch): HTMLElement | null {
  return element.querySelector<HTMLElement>(':scope > craft-button');
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('craft-input-search', () => {
  it('names the field "Search" and shows a search icon by default', async () => {
    const element = await createInputSearch();
    const input = element.querySelector('input')!;
    const label = element.querySelector(':scope > [slot="label"]')!;

    expect(element.label).toBe('Search');
    expect(element.hasAttribute('label-sr-only')).toBe(true);
    expect(label.textContent?.trim()).toBe('Search');
    expect(input.placeholder).toBe('Search');
    expect(
      element
        .querySelector(':scope > craft-icon[slot="prefix"]')
        ?.getAttribute('name')
    ).toBe('search');
  });

  it('uses a label it’s given', async () => {
    const element = await createInputSearch(
      '<craft-input-search label="Search entries" placeholder="Find…"></craft-input-search>'
    );

    expect(element.label).toBe('Search entries');
    expect(element.querySelector('input')!.placeholder).toBe('Find…');
  });

  it('keeps the search icon out of the accessibility tree', async () => {
    const element = await createInputSearch();
    const icon = element.querySelector('craft-icon[slot="prefix"]') as
      | (HTMLElement & {updateComplete: Promise<unknown>})
      | null;
    await icon!.updateComplete;

    expect(icon!.getAttribute('aria-hidden')).toBe('true');
    expect(icon!.hasAttribute('label')).toBe(false);
  });

  it('shows a named clear button only while the field has a value', async () => {
    const element = await createInputSearch();
    expect(clearButton(element)).toBeNull();

    await type(element, 'foo');
    const button = clearButton(element)!;
    expect(button.getAttribute('slot')).toBe('search-clear');
    expect(button.getAttribute('type')).toBe('button');
    expect(button.querySelector('craft-icon')!.getAttribute('label')).toBe(
      'Clear search'
    );

    await type(element, '');
    expect(clearButton(element)).toBeNull();
  });

  it('puts the clear button ahead of other suffix content in reading order', async () => {
    const element = await createInputSearch(
      '<craft-input-search><span slot="suffix" id="extra"></span></craft-input-search>'
    );
    await type(element, 'foo');

    const clearSlot = element.shadowRoot!.querySelector(
      'slot[name="search-clear"]'
    )!;
    const suffixSlot = element.shadowRoot!.querySelector(
      'slot[name="suffix"]'
    )!;
    expect(clearButton(element)!.getAttribute('slot')).toBe('search-clear');
    expect(
      clearSlot.compareDocumentPosition(suffixSlot) &
        Node.DOCUMENT_POSITION_FOLLOWING
    ).toBeTruthy();
  });

  it('clears the value and focuses the input when the clear button is pressed', async () => {
    const element = await createInputSearch();
    const events: string[] = [];
    element.addEventListener('clear', () => events.push('clear'));
    element.addEventListener('input', () => events.push('input'));
    await type(element, 'foo');
    events.length = 0;

    clearButton(element)!.click();
    await element.updateComplete;

    expect(element.modelValue).toBe('');
    expect(element.querySelector('input')!.value).toBe('');
    expect(document.activeElement).toBe(element.querySelector('input'));
    expect(events).toEqual(['input', 'clear']);
    expect(clearButton(element)).toBeNull();
  });

  it('fires clear and input past a shadow root it is rendered in', async () => {
    const host = document.createElement('div');
    document.body.append(host);
    host.attachShadow({mode: 'open'}).innerHTML =
      '<craft-input-search></craft-input-search>';
    const element = host.shadowRoot!.querySelector('craft-input-search')!;
    await element.updateComplete;
    const events: string[] = [];
    host.addEventListener('clear', () => events.push('clear'));
    host.addEventListener('input', () => events.push('input'));
    await type(element, 'foo');
    events.length = 0;

    element.clear();

    expect(events).toEqual(['input', 'clear']);
  });

  it('clears the value on Escape', async () => {
    const element = await createInputSearch();
    let reachedParent = false;
    document.body.addEventListener('keydown', () => (reachedParent = true));
    document.body.addEventListener('keyup', () => (reachedParent = true));
    await type(element, 'foo');

    const event = new KeyboardEvent('keydown', {
      key: 'Escape',
      bubbles: true,
      cancelable: true,
    });
    element.querySelector('input')!.dispatchEvent(event);
    await element.updateComplete;

    element
      .querySelector('input')!
      .dispatchEvent(
        new KeyboardEvent('keyup', {key: 'Escape', bubbles: true})
      );

    expect(element.modelValue).toBe('');
    expect(event.defaultPrevented).toBe(true);
    expect(reachedParent).toBe(false);

    const emptyEscape = new KeyboardEvent('keydown', {
      key: 'Escape',
      bubbles: true,
    });
    element.querySelector('input')!.dispatchEvent(emptyEscape);
    expect(reachedParent).toBe(true);
  });

  it('handles Escape before listeners on the input that empty it, even after moving', async () => {
    const element = await createInputSearch();
    const input = element.querySelector('input')!;
    let reachedParent = false;
    document.body.addEventListener('keydown', () => (reachedParent = true));
    input.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        input.value = '';
        input.dispatchEvent(new Event('input', {bubbles: true}));
      }
    });
    element.remove();
    document.body.append(element);
    await type(element, 'foo');

    input.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Escape', bubbles: true})
    );

    expect(element.modelValue).toBe('');
    expect(reachedParent).toBe(false);
  });

  it('doesn’t offer a clear button when disabled or read-only', async () => {
    const element = await createInputSearch();
    await type(element, 'foo');

    element.disabled = true;
    await element.updateComplete;
    expect(clearButton(element)).toBeNull();

    element.disabled = false;
    element.readOnly = true;
    await element.updateComplete;
    expect(clearButton(element)).toBeNull();

    element.readOnly = false;
    await element.updateComplete;
    expect(clearButton(element)).not.toBeNull();
  });
});
