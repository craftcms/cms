import {installCommandPaletteShortcut, registerProvider} from './registry';
import {onThisPageProvider} from './sources/on-this-page';

export * from './registry';
export type * from './types';
export {PAGE_ACTIONS_ATTRIBUTE} from './sources/on-page-actions';

/**
 * Installs the palette's page-level search provider and keyboard shortcut.
 * Imported once, by the control panel's entry point; the palette component
 * itself is rendered by `PageScreen`.
 */
registerProvider(onThisPageProvider);
installCommandPaletteShortcut();
