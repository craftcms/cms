import {AssetSelectInput} from '@/modules/asset-select-input/asset-select-input';
import {BaseElementSelectInput} from './base-element-select-input';
import {EntrySelectInput} from './entry-select-input';
import {TagSelectInput} from './tag-select-input';
import {registerCraftGlobals} from '@/common/craft-global';

// Plugins boot and subclass these by name, e.g. `Craft.AssetSelectInput.extend()`.
registerCraftGlobals({
  AssetSelectInput,
  BaseElementSelectInput,
  EntrySelectInput,
  TagSelectInput,
});
