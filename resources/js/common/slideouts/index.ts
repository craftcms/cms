import {closeAllSlideouts, closeSlideout, openSlideout} from './store';

export {default as SlideoutHost} from './SlideoutHost.vue';
export {useSlideout, useSlideoutOpener} from './useSlideout';
export {closeAllSlideouts, closeSlideout, openSlideout} from './store';
export type {SlideoutController, SlideoutInstance} from './types';

/**
 * Expose the opener on `window.Craft` so legacy bundles — and anyone poking at
 * the console — can open a Vue slideout without importing anything.
 */
export function registerSlideoutGlobals(): void {
  const craft = (window as any).Craft;

  if (!craft) {
    return;
  }

  craft.openSlideout = openSlideout;
  craft.closeSlideout = closeSlideout;
  craft.closeAllSlideouts = closeAllSlideouts;
}
