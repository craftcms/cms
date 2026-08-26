import {registerCraftGlobals} from '@/common/craft-global';
import {t} from '@craftcms/ui';
import {BaseElementSelectorModal} from './base-element-selector-modal';
import {AssetSelectorModal} from './asset-selector-modal';
import {VolumeFolderSelectorModal} from './volume-folder-selector-modal';

declare const Craft: any;

BaseElementSelectorModal.defaults.modalTitle ??= t('Select element');
BaseElementSelectorModal.defaults.selectBtnLabel ??= t('Select');

// Assign legacy `Craft.*` globals so PHP-emitted `new Craft.BaseElementSelectorModal(...)`
// and plugin code that calls `Craft.registerElementSelectorModalClass(...)` keep working.
registerCraftGlobals({
  BaseElementSelectorModal,
  AssetSelectorModal,
  VolumeFolderSelectorModal,
});

if (
  Craft._elementSelectorModalClasses?.[
    'CraftCms\\Cms\\Asset\\Elements\\Asset'
  ] === undefined
) {
  Craft.registerElementSelectorModalClass?.(
    'CraftCms\\Cms\\Asset\\Elements\\Asset',
    AssetSelectorModal
  );
}

export {BaseElementSelectorModal} from './base-element-selector-modal';
export {AssetSelectorModal} from './asset-selector-modal';
export {VolumeFolderSelectorModal} from './volume-folder-selector-modal';
