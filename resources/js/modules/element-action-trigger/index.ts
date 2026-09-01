import {ElementActionTrigger} from './element-action-trigger';
import {registerCraftGlobals} from '@/common/craft-global';

// `new Craft.ElementActionTrigger(...)` is emitted by PHP element actions and
// created by the (still-legacy) element index. Plain modern ES class.
registerCraftGlobals({ElementActionTrigger});

export {ElementActionTrigger};
