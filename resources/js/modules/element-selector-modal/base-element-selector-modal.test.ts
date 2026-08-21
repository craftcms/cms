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
    vi.stubGlobal('Craft', {
        t: (_category: string, message: string) => message,
        ui: {
            createSubmitButton: () => $('<button/>'),
        },
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
