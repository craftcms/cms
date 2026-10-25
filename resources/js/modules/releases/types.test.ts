import {afterEach, expect, it, vi} from 'vite-plus/test';
import {formatReleaseDate} from './types';

afterEach(() => {
  vi.unstubAllGlobals();
});

it('formats a release date in the system time zone, with its abbreviation', () => {
  vi.stubGlobal('Craft', {timezone: 'America/New_York'});

  expect(formatReleaseDate('2026-01-15T14:15:00Z')).toMatch(/9:15\sAM EST$/);
  expect(formatReleaseDate('2026-07-15T14:15:00Z')).toMatch(/10:15\sAM EDT$/);
  expect(formatReleaseDate(null)).toBe('');
});
