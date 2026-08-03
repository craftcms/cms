import {registerCraftGlobals} from '@/common/craft-global';
import {BaseElementSelectInput} from './base-element-select-input';
import {EntrySelectInput} from './entry-select-input';
import {TagSelectInput} from './tag-select-input';

// Assign legacy `Craft.*` globals so PHP-emitted `new Craft.BaseElementSelectInput({…})`
// and subclasses via `.extend()` keep working.
registerCraftGlobals({
    BaseElementSelectInput,
    EntrySelectInput,
    TagSelectInput,
});

export {BaseElementSelectInput} from './base-element-select-input';
export {EntrySelectInput} from './entry-select-input';
export {TagSelectInput} from './tag-select-input';
