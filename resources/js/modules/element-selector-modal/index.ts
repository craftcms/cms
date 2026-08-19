import {registerCraftGlobals} from '@/common/craft-global';
import {t} from '@craftcms/ui';
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

// Skipped when something got there first, so a plugin can replace the built-in
// asset modal without the duplicate-registration throw.
if (!hasElementSelectorModalClass(ASSET)) {
  registerElementSelectorModalClass(ASSET, AssetSelectorModal);
}

// The classes stay on `Craft` for PHP-emitted boots (`new
// Craft.VolumeFolderSelectorModal(…)`); the registry functions stay for plugins.
registerCraftGlobals({
  BaseElementSelectorModal,
  AssetSelectorModal,
  VolumeFolderSelectorModal,
  createElementSelectorModal,
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
