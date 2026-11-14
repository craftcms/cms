import {inject} from 'vue';
import {closeAllSlideouts, openSlideout} from './store';
import {SlideoutControllerKey, type SlideoutController} from './types';
import type {OpenSlideoutOptions, SlideoutSaveResult} from './types';

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

export interface SlideoutControl {
  /**
   * Close the slideout. Prompts when there are unsaved changes; pass
   * `{force: true}` for a close that follows a successful save.
   */
  close(options?: {force?: boolean}): void;
  /**
   * Report a successful save to the opener's `onSaved`. Call it before
   * {@link close}. Returns `false` when the opener didn't register one.
   */
  saved(result?: SlideoutSaveResult): boolean;
}

/** Closing the slideout the caller is in, or `null` on a full page. */
export function useSlideoutControl(): SlideoutControl | null {
  const slideout = useSlideout();
  if (!slideout) return null;
  return {
    close: (options) => slideout.close(options),
    saved: (result) => slideout.saved(result),
  };
}
