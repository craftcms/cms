import {reactive} from 'vue';

/**
 * Tracks which named layout regions pages have filled via `<LayoutSlot>`.
 *
 * `AppLayout` (and any other ambient layout) queries this registry through
 * `LayoutSlotOutlet` to reactively toggle its wrapper markup and fallback
 * content based on whether a page has mounted a matching `<LayoutSlot>`,
 * even though the page's content is teleported in independently.
 */
const counts = reactive(new Map<string, number>());

export function registerLayoutSlot(name: string): void {
  counts.set(name, (counts.get(name) ?? 0) + 1);
}

export function unregisterLayoutSlot(name: string): void {
  const next = (counts.get(name) ?? 0) - 1;
  if (next <= 0) {
    counts.delete(name);
  } else {
    counts.set(name, next);
  }
}

export function hasLayoutSlot(name: string): boolean {
  return (counts.get(name) ?? 0) > 0;
}
