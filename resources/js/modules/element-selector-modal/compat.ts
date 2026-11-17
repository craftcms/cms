import {createElementSelectorModal} from './create-element-selector-modal';
import {registerCraftGlobals} from '@/common/craft-global';

// Now async, resolving to a controller-backed handle rather than a modal
// object, since the modal is no longer a class anyone can subclass.
registerCraftGlobals({createElementSelectorModal});
