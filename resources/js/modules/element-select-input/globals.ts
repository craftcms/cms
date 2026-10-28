import {BaseElementSelectInput} from './base-element-select-input';
import {EntrySelectInput} from './entry-select-input';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign legacy `Craft.*` globals so PHP-emitted `new Craft.BaseElementSelectInput({…})`
// and subclasses via `.extend()` keep working.
registerCraftGlobals({BaseElementSelectInput, EntrySelectInput});
