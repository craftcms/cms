import {beforeEach, describe, expect, it} from 'vite-plus/test';
import {useRecentPages} from './recents';

describe('useRecentPages', () => {
  beforeEach(() => {
    (window as any).Craft = {systemUid: 'test'};
    localStorage.clear();
  });

  it('lists each page once, newest first, leaving out the current page', () => {
    const recents = useRecentPages();

    recents.record('https://example.test/admin/entries?sort=title', 'Entries');
    recents.record('https://example.test/admin/assets', 'Assets');
    recents.record('https://example.test/admin/entries?site=de', 'Entries');
    recents.record('https://example.test/admin/dashboard', 'Dashboard');

    expect(
      recents
        .items('https://example.test/admin/dashboard#widgets')
        .map(({label, href}) => [label, href])
    ).toEqual([
      ['Entries', 'https://example.test/admin/entries?site=de'],
      ['Assets', 'https://example.test/admin/assets'],
    ]);
  });
});
