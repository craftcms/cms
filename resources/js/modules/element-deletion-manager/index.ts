import {ElementDeletionManager} from './element-deletion-manager';
import {registerCraftGlobals} from '@/common/craft-global';

// `new Craft.ElementDeletionManager(...)` is created by the element index / PHP.
// Plain modern ES class (with a static `.Blocker` for BC).
registerCraftGlobals({ElementDeletionManager});

export {ElementDeletionManager};
