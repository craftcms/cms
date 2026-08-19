import {afterEach, expect, it, vi} from 'vite-plus/test';
import $ from 'jquery';
import {registerCraftGlobals} from '@/common/craft-global';
import {BaseElementSelectorModal} from './base-element-selector-modal';

afterEach(() => {
  vi.unstubAllGlobals();
  document.body.replaceChildren();
});

it('retains inherited modal settings during initialization', () => {
  const trigger = document.createElement('button');
  document.body.append(trigger);
  trigger.focus();

  vi.stubGlobal('$', $);
  // No `Craft.ui`: the chrome is built from Lit templates now, so nothing here
  // reaches for the legacy jQuery button factories.
  vi.stubGlobal('Craft', {
    t: (_category: string, message: string) => message,
  });

  class TestModal extends BaseElementSelectorModal {
    override show(): void {}
  }

  const modal = new TestModal('test');
  modal.init('test', {resizable: false});

  expect(modal.settings.hideOnEsc).toBe(true);
  expect(modal.settings.minGutter).toBe(10);
  expect(modal.settings.triggerElement).toBe(trigger);
  expect(() => modal.onFadeOut()).not.toThrow();

  modal.destroy();
});

function mountModal(settings: Record<string, any> = {}) {
  vi.stubGlobal('$', $);
  vi.stubGlobal('Craft', {
    t: (_category: string, message: string) => message,
  });

  class TestModal extends BaseElementSelectorModal {
    override show(): void {}
  }

  const modal = new TestModal('test');
  modal.init('test', settings);

  return {
    modal,
    container: document.querySelector<HTMLElement>(
      '.modal.elementselectormodal'
    )!,
  };
}

it('renders the chrome the CP stylesheet expects', () => {
  const {modal, container} = mountModal({
    modalTitle: 'Choose an asset',
    selectBtnLabel: 'Select',
    showTitle: true,
  });

  const heading = container.querySelector('h1')!;
  expect(heading.textContent).toBe('Choose an asset');
  expect(container.getAttribute('aria-labelledby')).toBe(heading.id);
  expect(heading.parentElement!.className).toBe('header');

  expect(container.querySelector('.body > .spinner.big')).not.toBeNull();
  expect(
    container.querySelector('.footer > .buttons.left.secondary-buttons')
  ).not.toBeNull();
  expect(container.querySelector('.footer > .buttons.right')).not.toBeNull();

  const cancelBtn = container.querySelector<HTMLButtonElement>(
    'button[type="button"]'
  )!;
  expect(cancelBtn.className).toBe('btn');
  expect(cancelBtn.textContent!.trim()).toBe('Cancel');

  // Reproduces `Craft.ui.createSubmitButton({class: 'disabled', spinner: true})`
  // — the CP stylesheet selects on every part of this.
  const selectBtn = container.querySelector<HTMLButtonElement>(
    'button[type="submit"]'
  )!;
  expect(selectBtn.className).toBe('btn disabled submit');
  expect(selectBtn.getAttribute('aria-disabled')).toBe('true');
  expect(
    selectBtn.querySelector('.inline-flex.gap-xs > .label')!.textContent!.trim()
  ).toBe('Select');
  expect(selectBtn.querySelector('.spinner.spinner-absolute')).not.toBeNull();

  // The `$`-prefixed fields stay jQuery wrappers around those same nodes,
  // because subclasses and the element select input manipulate them.
  expect(modal.$selectBtn[0]).toBe(selectBtn);
  expect(modal.$cancelBtn[0]).toBe(cancelBtn);
  expect(modal.$body[0]).toBe(container.querySelector('.body'));
  expect(modal.$footer[0]).toBe(container.querySelector('.footer'));

  modal.destroy();
});

it('hides the heading unless showTitle is set', () => {
  const {modal, container} = mountModal({modalTitle: 'Choose'});

  expect(container.querySelector('h1')!.parentElement!.className).toBe(
    'visually-hidden'
  );

  modal.destroy();
});

it('preserves legacy Craft class extension', () => {
  vi.stubGlobal('Craft', {});
  registerCraftGlobals({BaseElementSelectorModal});

  const RegisteredModal = window.Craft.BaseElementSelectorModal;
  const ExtendedModal = RegisteredModal.extend({
    init() {},
    hasSelection() {
      return !this.base();
    },
  });
  const modal = new ExtendedModal();

  expect(modal).toBeInstanceOf(BaseElementSelectorModal);
  expect(modal.hasSelection()).toBe(true);
  expect(ExtendedModal.ancestor).toBe(BaseElementSelectorModal);
});

it('exposes select button state controls to element select inputs', () => {
  expect(
    typeof Object.getOwnPropertyDescriptor(
      BaseElementSelectorModal.prototype,
      'enableSelectBtn'
    )?.value
  ).toBe('function');
  expect(
    typeof Object.getOwnPropertyDescriptor(
      BaseElementSelectorModal.prototype,
      'disableSelectBtn'
    )?.value
  ).toBe('function');
});

it('preserves legacy selector modal extension points', async () => {
  vi.stubGlobal('Craft', {
    registerElementSelectorModalClass: vi.fn(),
    t: (_category: string, message: string) => message,
  });

  const {AssetSelectorModal} = await import('./asset-selector-modal');
  const {VolumeFolderSelectorModal} =
    await import('./volume-folder-selector-modal');
  await import('./index');

  registerCraftGlobals({
    BaseElementSelectorModal,
    AssetSelectorModal,
    VolumeFolderSelectorModal,
  });

  expect((AssetSelectorModal as any).ancestor).toBe(BaseElementSelectorModal);
  expect((VolumeFolderSelectorModal as any).ancestor).toBe(
    BaseElementSelectorModal
  );

  for (const method of [
    'sidebarShouldBeHidden',
    'resetView',
    'buildSidebarToggleView',
    'sidebarIsOpen',
    'toggleSidebar',
    'openSidebar',
    'closeSidebar',
    'getActiveSourceName',
    'updateHeading',
  ]) {
    expect((BaseElementSelectorModal.prototype as any)[method]).toBeTypeOf(
      'function'
    );
  }

  for (const method of [
    'createSelectTransformButton',
    'onSelectTransform',
    'selectImagesWithTransform',
    'fetchMissingTransformUrls',
  ]) {
    expect((AssetSelectorModal.prototype as any)[method]).toBeTypeOf(
      'function'
    );
  }

  expect(BaseElementSelectorModal.defaults).toMatchObject({
    fullscreen: false,
    resizable: true,
    hideOnSelect: true,
    indexSettings: {},
    modalTitle: 'Select element',
    selectBtnLabel: 'Select',
  });
  expect(AssetSelectorModal.defaults).toMatchObject({
    canSelectImageTransforms: false,
    transforms: [],
  });
  expect(VolumeFolderSelectorModal.defaults).toMatchObject({
    disabledFolderIds: [],
    indexSettings: {},
  });

  class UninitializedModal extends BaseElementSelectorModal {}

  const modal = new UninitializedModal('test');
  for (const property of [
    '$sources',
    '$sourceToggles',
    '$sidebarToggleBtn',
    '$sidebarCloseBtn',
    '$mainHeading',
    '$search',
    '$elements',
    '$tbody',
  ]) {
    expect(property in modal).toBe(true);
  }
  modal.destroy();
});
