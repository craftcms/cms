import {beforeEach, describe, expect, it, vi} from 'vitest';
import {useFolderNavigation} from './useFolderNavigation';

const visitSpy = vi.fn();
const navigationRouter = {visit: visitSpy};

// The composable reads Craft.pageTrigger to know which param paginates.
window.Craft = Object.assign(Object.create(null), {pageTrigger: 'page'});

function visitedUrl(): URL {
  const href = visitSpy.mock.calls[0]?.[0];
  if (href?.constructor !== String)
    throw new Error('Expected a folder visit URL.');
  return new URL(String(href), 'http://localhost');
}

describe('useFolderNavigation', () => {
  beforeEach(() => {
    visitSpy.mockClear();
    window.history.replaceState({}, '', '/cp/assets/photos');
  });

  it('carries the current view query into the folder URL', () => {
    window.history.replaceState({}, '', '/cp/assets/photos?viewMode=cards');

    useFolderNavigation(navigationRouter).navigateToFolder(
      '/cp/assets/photos/sub'
    );

    const url = visitedUrl();
    expect(url.pathname).toBe('/cp/assets/photos/sub');
    expect(url.searchParams.get('viewMode')).toBe('cards');
  });

  it('drops the source and page params so the folder path is authoritative', () => {
    window.history.replaceState(
      {},
      '',
      '/cp/assets/photos?source=volume:abc&page=3&viewMode=cards'
    );

    useFolderNavigation(navigationRouter).navigateToFolder(
      '/cp/assets/photos/sub'
    );

    const url = visitedUrl();
    expect(url.searchParams.has('source')).toBe(false);
    expect(url.searchParams.has('page')).toBe(false);
    expect(url.searchParams.get('viewMode')).toBe('cards');
  });

  it('preserves the page component and scroll on the visit', () => {
    useFolderNavigation(navigationRouter).navigateToFolder(
      '/cp/assets/photos/sub'
    );

    expect(visitSpy).toHaveBeenCalledWith(
      expect.any(String),
      expect.objectContaining({preserveState: true, preserveScroll: true})
    );
  });

  it('only treats rows with a folder URL as folders', () => {
    const {isFolderRow} = useFolderNavigation(navigationRouter);
    expect(isFolderRow({isFolder: true, folderUrl: '/cp/assets/x'})).toBe(true);
    expect(isFolderRow({isFolder: true})).toBe(false);
    expect(isFolderRow({folderUrl: '/cp/assets/x'})).toBe(false);
    expect(isFolderRow({})).toBe(false);
  });
});
