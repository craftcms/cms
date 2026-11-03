import {AssetSelectInput} from './asset-select-input';
import {BaseElementSelectInput} from './base-element-select-input';
import {EntrySelectInput} from './entry-select-input';
import {TagSelectInput} from './tag-select-input';
import {registerCraftGlobals} from '@/common/craft-global';

// The jQuery-based inputs plugins boot and subclass by name, e.g.
// `Craft.AssetSelectInput.extend()`. Core uses its own classes.
registerCraftGlobals({
  AssetSelectInput,
  BaseElementSelectInput,
  EntrySelectInput,
  TagSelectInput,
});
