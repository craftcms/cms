import {PromptHandler} from './prompt-handler';
import {registerCraftGlobals} from '@/common/craft-global';

// Assign the legacy `Craft` global: `new Craft.PromptHandler()` is created by
// `Craft.AssetIndex` in the cp bundle. Plain modern ES class — nothing
// subclasses it via the legacy `.extend()` API.
registerCraftGlobals({PromptHandler});

export {PromptHandler};
