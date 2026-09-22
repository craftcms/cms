const QUERY_PARAM = 'debug-slots';
const STORAGE_KEY = 'craft:debug-layout-slots';

let enabled: boolean | null = null;

/**
 * Whether `LayoutSlotOutlet` should outline its region and label it with the
 * slot name.
 *
 * Turn it on with `?debug-slots` on any CP URL and off with `?debug-slots=0`;
 * the choice is remembered in `localStorage`. Read once per page load: the outlet's markup changes with
 * it, and swapping that mid-page would strand content already teleported into
 * the old target.
 */
export function layoutSlotDebugEnabled(): boolean {
  if (enabled !== null) {
    return enabled;
  }

  try {
    const param = new URLSearchParams(window.location.search).get(QUERY_PARAM);

    if (param !== null) {
      window.localStorage.setItem(STORAGE_KEY, param === '0' ? '0' : '1');
    }

    const stored = window.localStorage.getItem(STORAGE_KEY);
    enabled = stored === '1';
  } catch {
    enabled = false;
  }

  return enabled;
}

/** Forget the cached choice, so the next call reads it again. */
export function resetLayoutSlotDebug(): void {
  enabled = null;
}
