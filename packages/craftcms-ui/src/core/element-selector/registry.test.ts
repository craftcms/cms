import {afterEach, beforeEach, describe, expect, it} from 'vite-plus/test';
import {ElementSelectorController} from './element-selector-controller.js';
import {AssetSelectorController} from './asset-selector-controller.js';
import {
  adoptLegacyRegistrations,
  createElementSelectorController,
  elementSelectorControllerClass,
  hasElementSelectorController,
  registerElementSelectorController,
  resetElementSelectorControllers,
} from './registry.js';

const ENTRY = 'CraftCms\\Cms\\Entry\\Elements\\Entry';
const ASSET = 'CraftCms\\Cms\\Asset\\Elements\\Asset';

class CustomController extends ElementSelectorController {}

beforeEach(() => resetElementSelectorControllers());
afterEach(() => {
  resetElementSelectorControllers();
  delete (globalThis as any).window;
});

describe('registration', () => {
  it('falls back to the base controller for an unregistered type', () => {
    expect(elementSelectorControllerClass(ENTRY)).toBe(
      ElementSelectorController
    );
    expect(hasElementSelectorController(ENTRY)).toBe(false);
  });

  it('returns the registered class', () => {
    registerElementSelectorController(ASSET, AssetSelectorController);

    expect(elementSelectorControllerClass(ASSET)).toBe(AssetSelectorController);
    expect(hasElementSelectorController(ASSET)).toBe(true);
  });

  it('throws on a duplicate registration', () => {
    registerElementSelectorController(ASSET, AssetSelectorController);

    expect(() =>
      registerElementSelectorController(ASSET, CustomController)
    ).toThrow(/already been registered/);
  });
});

describe('createElementSelectorController', () => {
  it('builds the registered class', () => {
    registerElementSelectorController(ASSET, AssetSelectorController);

    expect(
      createElementSelectorController({elementType: ASSET})
    ).toBeInstanceOf(AssetSelectorController);
  });

  it('builds the base controller otherwise', () => {
    const controller = createElementSelectorController({elementType: ENTRY});

    expect(controller).toBeInstanceOf(ElementSelectorController);
    expect(controller.elementType).toBe(ENTRY);
  });

  it('passes the options through', () => {
    const controller = createElementSelectorController({
      elementType: ENTRY,
      multiSelect: true,
    });

    expect(controller.options.multiSelect).toBe(true);
  });
});

describe('adoptLegacyRegistrations', () => {
  it('is a no-op without a window, so core stays node-testable', () => {
    expect(() => adoptLegacyRegistrations()).not.toThrow();
  });

  it('tolerates a missing legacy registry', () => {
    (globalThis as any).window = {Craft: {}};

    expect(() => adoptLegacyRegistrations()).not.toThrow();
  });

  it('drains classes a plugin registered before this module loaded', () => {
    (globalThis as any).window = {
      Craft: {_elementSelectorModalClasses: {[ENTRY]: CustomController}},
    };

    adoptLegacyRegistrations();

    expect(elementSelectorControllerClass(ENTRY)).toBe(CustomController);
  });

  it('leaves an existing modern registration alone', () => {
    registerElementSelectorController(ASSET, AssetSelectorController);
    (globalThis as any).window = {
      Craft: {_elementSelectorModalClasses: {[ASSET]: CustomController}},
    };

    adoptLegacyRegistrations();

    expect(elementSelectorControllerClass(ASSET)).toBe(AssetSelectorController);
  });
});
