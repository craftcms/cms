import {createGlobalState} from '@vueuse/core';
import {useLocalStorage} from '@/common/composables/useStorage';
import type {CommandPaletteItem} from './types';

/** How many pages the palette lists under “Recents”. */
export const RECENT_PAGE_LIMIT = 5;

/** One more than is shown, so the page you're on can be left out of a full list. */
const STORED_LIMIT = RECENT_PAGE_LIMIT + 1;

interface RecentPage {
  url: string;
  title: string;
}

/**
 * The control panel pages visited most recently, newest first.
 *
 * Kept in the browser rather than on the server: it's a convenience for this
 * person on this device, and recording every page view server-side would cost
 * a request each time for something only the palette reads.
 */
export const useRecentPages = createGlobalState(() => {
  const pages = useLocalStorage<RecentPage[]>('commandPalette.recentPages', []);

  function record(url: string, title: string): void {
    const trimmedTitle = title.trim();

    if (!trimmedTitle) {
      return;
    }

    const key = pageKey(url);

    pages.value = [
      {url, title: trimmedTitle},
      ...(Array.isArray(pages.value) ? pages.value : []).filter(
        (page) => pageKey(page.url) !== key
      ),
    ].slice(0, STORED_LIMIT);
  }

  /** Recent pages other than the one being viewed. */
  function items(currentUrl: string): CommandPaletteItem[] {
    const seen = new Set([pageKey(currentUrl)]);

    return (Array.isArray(pages.value) ? pages.value : [])
      .filter((page) => {
        const key = pageKey(page.url);

        if (seen.has(key)) {
          return false;
        }

        seen.add(key);

        return true;
      })
      .slice(0, RECENT_PAGE_LIMIT)
      .map((page) => ({
        id: `recent-${page.url}`,
        label: page.title,
        icon: 'clock-rotate-left',
        href: page.url,
      }));
  }

  return {record, items};
});

/**
 * The page a URL is for, ignoring its query string and fragment: a sort order,
 * a selected site or tab is still the same page.
 */
export function pageKey(url: string): string {
  try {
    const parsed = new URL(url, window.location.origin);

    return parsed.origin + parsed.pathname;
  } catch {
    return url;
  }
}
