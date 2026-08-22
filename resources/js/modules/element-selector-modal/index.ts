import {registerCraftGlobals} from '@/common/craft-global';
import {
  AssetSelectorController,
  adoptLegacyRegistrations as adoptLegacyControllerRegistrations,
  hasElementSelectorController,
  registerElementSelectorController,
  t,
} from '@craftcms/ui';
import {createElementSelectorModal as createModal} from './create-element-selector-modal';
import {BaseElementSelectorModal} from './base-element-selector-modal';
import {AssetSelectorModal} from './asset-selector-modal';
import {VolumeFolderSelectorModal} from './volume-folder-selector-modal';
import {
  adoptLegacyRegistrations,
  createElementSelectorModal,
  hasElementSelectorModalClass,
  registerElementSelectorModalClass,
} from './registry';

const ASSET = 'CraftCms\\Cms\\Asset\\Elements\\Asset';

BaseElementSelectorModal.defaults.modalTitle ??= t('Select element');
BaseElementSelectorModal.defaults.selectBtnLabel ??= t('Select');

adoptLegacyRegistrations();
adoptLegacyControllerRegistrations();

// Skipped when something got there first, so a plugin can replace the built-in
// asset modal without the duplicate-registration throw.
if (!hasElementSelectorModalClass(ASSET)) {
  registerElementSelectorModalClass(ASSET, AssetSelectorModal);
}

// Assets get transform support on the modern path. Same yield-to-a-plugin rule.
if (!hasElementSelectorController(ASSET)) {
  registerElementSelectorController(ASSET, AssetSelectorController);
}

// The classes stay on `Craft` for PHP-emitted boots (`new
// Craft.VolumeFolderSelectorModal(…)`); the registry functions stay for plugins.
registerCraftGlobals({
  BaseElementSelectorModal,
  AssetSelectorModal,
  VolumeFolderSelectorModal,
  // The modern factory: async, and returns a controller-backed handle.
  createElementSelectorModal: createModal,
  registerElementSelectorModalClass,
});

export {BaseElementSelectorModal} from './base-element-selector-modal';
export {AssetSelectorModal} from './asset-selector-modal';
export {VolumeFolderSelectorModal} from './volume-folder-selector-modal';
export {
  createElementSelectorModal,
  elementSelectorModalClass,
  registerElementSelectorModalClass,
  type ElementSelectorModalClass,
} from './registry';
