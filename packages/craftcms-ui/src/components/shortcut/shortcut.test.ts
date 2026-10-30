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

/** The modifier keycaps the component renders, as shown. */
function modifiers(element: CraftShortcut): string[] {
  return [...element.shadowRoot!.querySelectorAll('kbd')]
    .filter((key) => !key.querySelector('slot'))
    .map((key) =>
      (key.querySelector('[aria-hidden="true"]') ?? key).textContent!.trim()
    );
}

/** What assistive technology reads for each modifier keycap. */
function modifierNames(element: CraftShortcut): string[] {
  return [...element.shadowRoot!.querySelectorAll('kbd')]
    .filter((key) => !key.querySelector('slot'))
    .map((key) =>
      (key.querySelector('.cp-visually-hidden') ?? key).textContent!.trim()
    );
}

beforeEach(() => {
  document.body.innerHTML = '';
});

afterEach(() => {
  vi.restoreAllMocks();
});

describe('craft-shortcut', () => {
  it('uses the platform symbols on a Mac', async () => {
    expect(modifiers(await createShortcut({os: 'Mac'}))).toEqual(['⌘']);
    expect(modifiers(await createShortcut({os: 'Mac', alt: ''}))).toEqual([
      '⌥',
      '⌘',
    ]);
    expect(modifiers(await createShortcut({os: 'Mac', shift: ''}))).toEqual([
      '⇧',
      '⌘',
    ]);
    expect(
      modifiers(await createShortcut({os: 'Mac', alt: '', shift: ''}))
    ).toEqual(['⌥', '⇧', '⌘']);
  });

  it('spells the modifiers out elsewhere', async () => {
    expect(modifiers(await createShortcut({os: 'Windows'}))).toEqual(['Ctrl']);
    expect(modifiers(await createShortcut({os: 'Windows', alt: ''}))).toEqual([
      'Ctrl',
      'Alt',
    ]);
    expect(modifiers(await createShortcut({os: 'Linux'}))).toEqual(['Super']);
    expect(modifiers(await createShortcut({os: 'Linux', shift: ''}))).toEqual([
      'Super',
      'Shift',
    ]);
  });

  /** An unknown platform falls back to the Ctrl spelling rather than nothing. */
  it('falls back to Ctrl for an unknown platform', async () => {
    // `Unknown` is also what sends the component back to `navigator.platform`,
    // which happy-dom takes from the host — so on a Linux runner it would come
    // back as Linux. Pin it to something no platform check recognizes.
    vi.spyOn(navigator, 'platform', 'get').mockReturnValue('Plan 9');

    expect(modifiers(await createShortcut({os: 'Unknown'}))).toEqual(['Ctrl']);
  });

  it('renders the key from the default slot', async () => {
    const element = await createShortcut({os: 'Mac'}, 'K');

    expect(element.textContent).toBe('K');
    expect(element.shadowRoot!.querySelector('kbd slot')).toBeTruthy();
  });

  it('puts each key in a keycap of its own', async () => {
    const element = await createShortcut({os: 'Mac', shift: ''}, 'K');

    expect(element.shadowRoot!.querySelectorAll('kbd')).toHaveLength(3);
  });

  it('names the Mac symbols for assistive technology', async () => {
    expect(
      modifierNames(await createShortcut({os: 'Mac', alt: '', shift: ''}))
    ).toEqual(['Option', 'Shift', 'Command']);
  });
});
