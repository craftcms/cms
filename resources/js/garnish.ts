/**
 * Garnish, published to plugin bundles through the import map (see
 * `Cp::sharedModules()`) so a plugin's drag-and-drop, modals and menus share
 * the control panel's instance rather than bundling their own copy.
 */
export * from '@craftcms/garnish';
