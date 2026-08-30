import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';

const ASSET = 'CraftCms\\Cms\\Asset\\Elements\\Asset';

/**
 * Imports the module under test and the registry it writes into, together.
 *
 * The registry is a module singleton in `@craftcms/ui`, and `vi.resetModules()`
 * hands each test a fresh module graph — so the registry has to be read from the
 * *same* graph as `./index`, not from a stale top-level import.
 */
async function importModule() {
  await import('./index');

  return import('@craftcms/ui');
}

beforeEach(() => {
  vi.resetModules();
});

afterEach(() => {
  vi.unstubAllGlobals();
  vi.resetModules();
});

it('owns the registry rather than reaching into the legacy bundle', async () => {
  vi.stubGlobal('Craft', {});

  const {AssetSelectorController, elementSelectorControllerClass} =
    await importModule();
  const Craft = (window as any).Craft;

  // The factory and the registration hook come from here, so the legacy bundle
  // no longer has to ship either.
  expect(typeof Craft.createElementSelectorModal).toBe('function');
  expect(typeof Craft.registerElementSelectorController).toBe('function');
  // Still constructible from PHP-emitted boots.
  expect(typeof Craft.VolumeFolderSelectorModal).toBe('function');

  expect(elementSelectorControllerClass(ASSET)).toBe(AssetSelectorController);
});

it('yields to a controller the legacy bundle already registered for assets', async () => {
  class PluginAssetController {}
  vi.stubGlobal('Craft', {
    _elementSelectorModalClasses: {[ASSET]: PluginAssetController},
  });

  const {elementSelectorControllerClass} = await importModule();

  expect(elementSelectorControllerClass(ASSET)).toBe(PluginAssetController);
});
