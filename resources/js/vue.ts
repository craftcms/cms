/**
 * The CP's own Vue instance, published to plugin bundles through the import
 * map (see `Cp::registerImportMap()`) so their components share one runtime
 * with the CP rather than each bundling their own copy.
 */
export * from 'vue';
