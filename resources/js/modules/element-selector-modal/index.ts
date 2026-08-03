import {registerCraftGlobals} from '@/common/craft-global';
import {BaseElementSelectorModal} from './base-element-selector-modal';
import {AssetSelectorModal} from './asset-selector-modal';
import {VolumeFolderSelectorModal} from './volume-folder-selector-modal';

// Assign legacy `Craft.*` globals so PHP-emitted `new Craft.BaseElementSelectorModal(...)`
// and plugin code that calls `Craft.registerElementSelectorModalClass(...)` keep working.
registerCraftGlobals({
    BaseElementSelectorModal,
    AssetSelectorModal,
    VolumeFolderSelectorModal,
});

export {BaseElementSelectorModal} from './base-element-selector-modal';
export {AssetSelectorModal} from './asset-selector-modal';
export {VolumeFolderSelectorModal} from './volume-folder-selector-modal';
