import '@/modules/element-thumb-loader';
import './globals';
import CraftElementSelectInput, {
  CraftAssetSelectInput,
  CraftEntrySelectInput,
} from './element-select-input.ce';
import {defineElement} from '@/common/web-components';

defineElement('craft-asset-select-input', CraftAssetSelectInput);
defineElement('craft-element-select-input', CraftElementSelectInput);
defineElement('craft-entry-select-input', CraftEntrySelectInput);

export {BaseElementSelectInput} from './base-element-select-input';
export {EntrySelectInput} from './entry-select-input';
export {TagSelectInput} from './tag-select-input';
export {CraftAssetSelectInput, CraftElementSelectInput, CraftEntrySelectInput};
export {elementSelectInputData} from './support';
