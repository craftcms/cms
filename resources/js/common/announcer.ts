/**
 * Screen reader announcements.
 *
 * A message goes to the live region of the topmost modal layer (a modal or
 * slideout), since everything behind it is hidden from assistive technology.
 * With no modal open it goes to the page's region: `#global-live-region` on
 * legacy pages, and `<LiveRegion>` (which renders {@link announcement}) on
 * Inertia pages.
 */

import {ref} from 'vue';
import {Garnish} from '@craftcms/garnish';

export interface AnnounceOptions {
  /** How long the message stays in the region, in milliseconds. */
  timeout: number;
}

/** The page-level announcement, for `<LiveRegion>` to render. */
export const announcement = ref<string | null>(null);

let clear: (() => void) | null = null;
let timer: ReturnType<typeof setTimeout> | null = null;

export function announce(
  message: string | null | undefined,
  options: Partial<AnnounceOptions> = {}
): void {
  if (!message) {
    return;
  }

  if (timer !== null) {
    clearTimeout(timer);
  }
  clear?.();

  const region = activeRegion();

  if (region) {
    // Assigning `textContent` both clears any previous announcement and sets the new one
    region.textContent = message;
    clear = () => {
      region.textContent = '';
    };
  } else {
    announcement.value = message;
    clear = () => {
      announcement.value = null;
    };
  }

  timer = setTimeout(() => {
    clear?.();
    clear = null;
    timer = null;
  }, options.timeout || 5000);
}

/** The region to write to, or `null` to use {@link announcement}. */
function activeRegion(): Element | null {
  const modal = topModalLayer();

  if (modal) {
    // Modals add theirs as a direct child; legacy slideouts to the inner `.slideout`
    return modal.querySelector(
      ':scope > [role="status"], :scope > .slideout > [role="status"]'
    );
  }

  return document.getElementById('global-live-region');
}

function topModalLayer(): Element | null {
  // Legacy pages keep their layers in the legacy bundle's own manager
  const legacy = Object.getOwnPropertyDescriptor(window, 'Garnish')?.value as
    | {uiLayerManager?: typeof Garnish.uiLayerManager}
    | undefined;
  const manager = legacy?.uiLayerManager ?? Garnish.uiLayerManager;

  const container = manager?.highestModalLayer?.$container as
    | Element
    | {0?: Element}
    | null
    | undefined;

  // Layers from the legacy bundle hold a jQuery collection
  const element = container instanceof Element ? container : container?.[0];

  return element && document.contains(element) ? element : null;
}

/** Points `Craft.cp.announce()` here. */
export function installAnnouncer(): void {
  const proto = (
    window as {Craft?: {CP?: {prototype: Record<string, unknown>}}}
  ).Craft?.CP?.prototype;

  if (proto) {
    proto.announce = (message: string) => announce(message);
  }
}
