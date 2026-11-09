import {resolveIcon} from '@craftcms/ui';
import {render} from 'lit';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {configureIcons} from './icons';

describe('configureIcons', () => {
  const fetch = vi.fn(
    async () =>
      new Response('<svg viewBox="0 0 512 512"><path d="M0 0h1"/></svg>')
  );

  afterEach(() => {
    vi.unstubAllGlobals();
    fetch.mockClear();
  });

  it.each([
    ['world', 'earth-europe'],
    ['earth', 'earth-asia'],
    ['light/world', 'earth-africa'],
  ])(
    'resolves %s to the globe for the system time zone',
    async (name, earthIcon) => {
      vi.stubGlobal('fetch', fetch);
      configureIcons('/icons', earthIcon);

      await resolveIcon(name);

      expect(fetch).toHaveBeenCalledWith(
        expect.stringMatching(new RegExp(`/${earthIcon}\\.svg$`)),
        expect.anything()
      );
    }
  );

  it.each([
    ['structurertl', 'classic', 'solid', '/icons/solid/list-tree.svg'],
    ['share-flip', 'custom-icons', 'solid', '/icons/solid/share.svg'],
    [
      'custom-icons/sidebar-nav-flip-light',
      'classic',
      'solid',
      '/icons/custom-icons/sidebar-nav-light.svg',
    ],
  ])(
    'resolves %s to its original icon, mirrored',
    async (name, family, variant, url) => {
      vi.stubGlobal('fetch', fetch);
      configureIcons('/icons');

      const container = document.createElement('div');
      render(await resolveIcon(name, family, variant), container);

      expect(fetch).toHaveBeenCalledWith(url, expect.anything());
      expect(container.querySelector('svg')?.getAttribute('style')).toBe(
        'scale: -1 1'
      );
    }
  );

  it('does not mirror the original icon once a flipped one has loaded', async () => {
    vi.stubGlobal('fetch', fetch);
    configureIcons('/icons');

    const flipped = document.createElement('div');
    const original = document.createElement('div');
    render(await resolveIcon('list-flip', 'classic', 'solid'), flipped);
    render(await resolveIcon('list', 'classic', 'solid'), original);

    expect(flipped.querySelector('svg')?.getAttribute('style')).toBe(
      'scale: -1 1'
    );
    expect(original.querySelector('svg')).not.toBeNull();
    expect(original.querySelector('svg')?.hasAttribute('style')).toBe(false);
  });
});
