import {registerCraftGlobals} from '@/common/craft-global';
import '@/modules/element-thumb-loader';
import {BaseElementSelectInput} from './base-element-select-input';
import {EntrySelectInput} from './entry-select-input';
import {TagSelectInput} from './tag-select-input';
import CraftElementSelectInput, {
  CraftAssetSelectInput,
  CraftEntrySelectInput,
} from './element-select-input.ce';
import {defineElement} from '@/common/web-components';

// Assign legacy `Craft.*` globals so PHP-emitted `new Craft.BaseElementSelectInput({…})`
// and subclasses via `.extend()` keep working.
registerCraftGlobals({
  BaseElementSelectInput,
  EntrySelectInput,
  TagSelectInput,
});

defineElement('craft-asset-select-input', CraftAssetSelectInput);
defineElement('craft-element-select-input', CraftElementSelectInput);
defineElement('craft-entry-select-input', CraftEntrySelectInput);

export {BaseElementSelectInput} from './base-element-select-input';
export {EntrySelectInput} from './entry-select-input';
export {TagSelectInput} from './tag-select-input';
export {CraftAssetSelectInput, CraftElementSelectInput, CraftEntrySelectInput};
export {elementSelectInputData} from './support';
