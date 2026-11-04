import {resolveIcon} from '@craftcms/ui';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {configureIcons} from './icons';

describe('configureIcons', () => {
  const fetch = vi.fn(async () => new Response('<svg></svg>'));

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
});
