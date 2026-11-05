import {FieldToggle} from './field-toggle';
import {registerCraftGlobals} from '@/common/craft-global';

// For `$.fn.fieldtoggle` and `new Craft.FieldToggle(...)` in legacy code and plugins
registerCraftGlobals({FieldToggle});
