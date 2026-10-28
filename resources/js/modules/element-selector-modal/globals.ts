import {VolumeFolderSelectorModal} from './volume-folder-selector-modal';
import {registerCraftGlobals} from '@/common/craft-global';

// `new Craft.VolumeFolderSelectorModal(…)` is emitted by `MoveAssets.php` and
// created by the legacy `AssetIndex`.
registerCraftGlobals({VolumeFolderSelectorModal});
