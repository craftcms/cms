import {AssetSelectInput} from './asset-select-input';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft.AssetSelectInput` global so PHP-emitted code and the
// still-legacy cp bundle keep working.
registerCraftGlobals({AssetSelectInput});
