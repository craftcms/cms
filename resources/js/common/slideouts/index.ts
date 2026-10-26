import {createElementEditor} from './create-element-editor';
import {closeAllSlideouts, closeSlideout, openSlideout} from './store';

export {default as SlideoutHost} from './SlideoutHost.vue';
export {createElementEditor, ElementEditorPanel} from './create-element-editor';
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
 * the console — can open a Vue slideout without importing anything. This also
 * replaces the legacy bundle's `Craft.createElementEditor()`, so element
 * editors open in a Vue slideout too.
 */
export function registerSlideoutGlobals(): void {
  const craft = window.Craft;

  if (!craft) {
    return;
  }

  Object.assign(craft, {
    openSlideout,
    closeSlideout,
    closeAllSlideouts,
    createElementEditor,
  });
}
