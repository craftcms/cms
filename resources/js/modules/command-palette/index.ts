import {
  closeCommandPalette,
  installCommandPaletteShortcut,
  openCommandPalette,
  registerActions,
  registerProvider,
  registerView,
} from './registry';
import {onThisPageProvider} from './sources/on-this-page';

export * from './registry';
export type * from './types';
export {PAGE_ACTIONS_ATTRIBUTE} from './sources/on-page-actions';

/**
 * Installs the palette's page-level search provider and `Craft.commandPalette`.
 * Imported once, by the control panel's entry point; the palette component
 * itself is rendered by `PageScreen`.
 */
registerProvider(onThisPageProvider);
installCommandPaletteShortcut();

/**
 * `Craft.commandPalette`, for plugins: open or close the palette, and add
 * client-side search providers, page actions and views. Server-side search
 * providers, pages and actions are registered in PHP.
 */
Object.assign(window.Craft, {
  commandPalette: {
    open: openCommandPalette,
    close: closeCommandPalette,
    registerProvider,
    registerActions,
    registerView,
  },
});
