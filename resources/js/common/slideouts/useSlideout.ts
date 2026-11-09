import {inject} from 'vue';
import {closeAllSlideouts, openSlideout} from './store';
import {SlideoutControllerKey, type SlideoutController} from './types';
import type {OpenSlideoutOptions} from './types';

/**
 * Open a slideout from anywhere.
 *
 * Not a composable in the reactive sense — it can be called outside setup, so
 * legacy code and event handlers can reach it too.
 */
export function useSlideoutOpener() {
  return {
    open: (href: string, options?: OpenSlideoutOptions) =>
      openSlideout(href, options),
    closeAll: closeAllSlideouts,
  };
}

/**
 * The slideout the calling component is rendering inside, or `null` on a full
 * page. Page components use this to close themselves after a save.
 */
export function useSlideout(): SlideoutController | null {
  return inject(SlideoutControllerKey, null);
}

/**
 * The id of a slideout screen's title. The panel names its dialog after it,
 * and focuses it on open.
 */
export function slideoutTitleId(containerId: string): string {
  return `${containerId}-title`;
}
