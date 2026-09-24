import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';

import './shortcut.js';
import type CraftShortcut from './shortcut.js';

async function createShortcut(
  attrs: Record<string, string> = {},
  innerHTML = 'S'
): Promise<CraftShortcut> {
  const element = document.createElement('craft-shortcut') as CraftShortcut;
  for (const [name, value] of Object.entries(attrs)) {
    element.setAttribute(name, value);
  }
  element.innerHTML = innerHTML;
  document.body.append(element);
  await element.updateComplete;
  return element;
}

/** The prefix the component renders, without the slotted key. */
function prefix(element: CraftShortcut): string {
  return element.shadowRoot!.querySelector('.shortcut')!.textContent!.trim();
}

beforeEach(() => {
  document.body.innerHTML = '';
});

afterEach(() => {
  vi.restoreAllMocks();
});

describe('craft-shortcut', () => {
  it('uses the platform symbols on a Mac', async () => {
    expect(prefix(await createShortcut({os: 'Mac'}))).toBe('⌘');
    expect(prefix(await createShortcut({os: 'Mac', alt: ''}))).toBe('⌥⌘');
    expect(prefix(await createShortcut({os: 'Mac', shift: ''}))).toBe('⇧⌘');
    expect(prefix(await createShortcut({os: 'Mac', alt: '', shift: ''}))).toBe(
      '⌥⇧⌘'
    );
  });

  it('spells the modifiers out elsewhere', async () => {
    expect(prefix(await createShortcut({os: 'Windows'}))).toBe('Ctrl+');
    expect(prefix(await createShortcut({os: 'Windows', alt: ''}))).toBe(
      'Ctrl+Alt+'
    );
    expect(prefix(await createShortcut({os: 'Linux'}))).toBe('Super+');
    expect(prefix(await createShortcut({os: 'Linux', shift: ''}))).toBe(
      'Super+Shift+'
    );
  });

  /** An unknown platform falls back to the Ctrl spelling rather than nothing. */
  it('falls back to Ctrl for an unknown platform', async () => {
    // `Unknown` is also what sends the component back to `navigator.platform`,
    // which happy-dom takes from the host — so on a Linux runner it would come
    // back as Linux. Pin it to something no platform check recognizes.
    vi.spyOn(navigator, 'platform', 'get').mockReturnValue('Plan 9');

    expect(prefix(await createShortcut({os: 'Unknown'}))).toBe('Ctrl+');
  });

  it('renders the key from the default slot', async () => {
    const element = await createShortcut({os: 'Mac'}, 'K');

    expect(element.textContent).toBe('K');
    expect(element.shadowRoot!.querySelector('slot')).toBeTruthy();
  });
});
