import {registerCraftGlobals} from '@/common/craft-global';
import {AssetSelectInput} from './asset-select-input';

// Assign the legacy `Craft.AssetSelectInput` global so PHP-emitted code and the
// still-legacy cp bundle keep working.
registerCraftGlobals({AssetSelectInput});

export {AssetSelectInput};
