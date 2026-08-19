import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {BaseElementSelectorModal} from './base-element-selector-modal';
import {
  adoptLegacyRegistrations,
  createElementSelectorModal,
  elementSelectorModalClass,
  hasElementSelectorModalClass,
  registerElementSelectorModalClass,
  resetElementSelectorModalClasses,
} from './registry';

class FakeModal {
  constructor(
    public elementType: string,
    public settings?: Record<string, any>
  ) {}
}

const ENTRY = 'CraftCms\\Cms\\Entry\\Elements\\Entry';

beforeEach(() => resetElementSelectorModalClasses());

afterEach(() => {
  resetElementSelectorModalClasses();
  vi.unstubAllGlobals();
});

it('falls back to the base modal for an unregistered element type', () => {
  expect(elementSelectorModalClass(ENTRY)).toBe(BaseElementSelectorModal);
  expect(hasElementSelectorModalClass(ENTRY)).toBe(false);
});

it('constructs the registered class with the element type and settings', () => {
  registerElementSelectorModalClass(ENTRY, FakeModal as never);

  const modal = createElementSelectorModal(ENTRY, {modalTitle: 'Choose'});

  expect(modal).toBeInstanceOf(FakeModal);
  expect((modal as unknown as FakeModal).elementType).toBe(ENTRY);
  expect((modal as unknown as FakeModal).settings).toEqual({
    modalTitle: 'Choose',
  });
});

it('refuses a second registration for one element type', () => {
  registerElementSelectorModalClass(ENTRY, FakeModal as never);

  expect(() =>
    registerElementSelectorModalClass(ENTRY, FakeModal as never)
  ).toThrow(/already been registered/);
});

it('adopts classes the legacy bundle registered before it loaded', () => {
  vi.stubGlobal('Craft', {_elementSelectorModalClasses: {[ENTRY]: FakeModal}});

  adoptLegacyRegistrations();

  expect(elementSelectorModalClass(ENTRY)).toBe(FakeModal);
});

it('keeps its own registration when the legacy bundle claims the same type', () => {
  class Newer {}
  registerElementSelectorModalClass(ENTRY, Newer as never);
  vi.stubGlobal('Craft', {_elementSelectorModalClasses: {[ENTRY]: FakeModal}});

  adoptLegacyRegistrations();

  expect(elementSelectorModalClass(ENTRY)).toBe(Newer);
});

it('tolerates the legacy registry being gone', () => {
  vi.stubGlobal('Craft', {});

  expect(() => adoptLegacyRegistrations()).not.toThrow();
});
