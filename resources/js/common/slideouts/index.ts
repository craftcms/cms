import {closeAllSlideouts, closeSlideout, openSlideout} from './store';

export {default as SlideoutHost} from './SlideoutHost.vue';
export {useSlideout, useSlideoutOpener} from './useSlideout';
export {
  closeAllSlideouts,
  closeSlideout,
  openSlideout,
  openSlideoutWith,
} from './store';
export type {
  OpenSlideoutOptions,
  SlideoutController,
  SlideoutInstance,
  SlideoutSaveResult,
} from './types';

/**
 * Expose the opener on `window.Craft` so legacy bundles — and anyone poking at
 * the console — can open a Vue slideout without importing anything.
 */
export function registerSlideoutGlobals(): void {
  const craft = window.Craft;

  if (!craft) {
    return;
  }

  Object.assign(craft, {openSlideout, closeSlideout, closeAllSlideouts});
}
