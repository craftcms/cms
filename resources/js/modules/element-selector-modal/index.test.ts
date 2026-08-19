import {afterEach, expect, it, vi} from 'vite-plus/test';

afterEach(() => {
  vi.unstubAllGlobals();
  vi.resetModules();
});

it('owns the registry rather than reaching into the legacy bundle', async () => {
  vi.stubGlobal('Craft', {});

  const module = await import('./index');
  const Craft = (window as any).Craft;

  // The factory and the registration hook now come from here, so the legacy
  // bundle no longer has to ship either.
  expect(typeof Craft.createElementSelectorModal).toBe('function');
  expect(typeof Craft.registerElementSelectorModalClass).toBe('function');
  expect(
    module.elementSelectorModalClass('CraftCms\\Cms\\Asset\\Elements\\Asset')
  ).toBe(module.AssetSelectorModal);
});

it('yields to a modal class the legacy bundle already registered for assets', async () => {
  class PluginAssetModal {}
  vi.stubGlobal('Craft', {
    _elementSelectorModalClasses: {
      'CraftCms\\Cms\\Asset\\Elements\\Asset': PluginAssetModal,
    },
  });

  const module = await import('./index');

  expect(
    module.elementSelectorModalClass('CraftCms\\Cms\\Asset\\Elements\\Asset')
  ).toBe(PluginAssetModal);
});
