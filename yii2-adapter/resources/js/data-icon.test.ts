import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {observeDataIcons} from './data-icon';

const observers: MutationObserver[] = [];

afterEach(() => {
  observers.splice(0).forEach((observer) => observer.disconnect());
  document.body.replaceChildren();
  vi.restoreAllMocks();
});

function icons(element: Element) {
  return [...element.querySelectorAll('craft-icon')].map((icon) =>
    icon.getAttribute('name')
  );
}

describe('legacy data-icon glyphs', () => {
  it('renders existing and later-added glyphs as a single craft-icon each', async () => {
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    document.body.innerHTML =
      '<a id="settings" data-icon="settings">Settings</a>';
    const root = document.createElement('div');
    document.body.append(root);
    observers.push(observeDataIcons(document.body));

    const button = document.createElement('button');
    button.setAttribute('data-icon', 'edit');
    button.textContent = 'Edit';
    root.append(button);
    await Promise.resolve();

    const link = document.getElementById('settings')!;
    expect(link.firstElementChild?.tagName).toBe('CRAFT-ICON');
    expect(link.textContent).toBe('Settings');
    expect(icons(link)).toEqual(['settings']);
    expect(icons(button)).toEqual(['edit']);

    button.setAttribute('data-icon', 'trash');
    await Promise.resolve();

    expect(icons(button)).toEqual(['trash']);
  });

  it('leaves data-icon alone on custom elements and void elements', async () => {
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    document.body.innerHTML =
      '<craft-field data-icon="i-cursor"><input data-icon="search"></craft-field>';
    observers.push(observeDataIcons(document.body));
    await Promise.resolve();

    expect(document.querySelectorAll('craft-icon')).toHaveLength(0);
  });
});
