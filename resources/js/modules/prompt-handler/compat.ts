import {PromptHandler} from './prompt-handler';
import {registerCraftGlobals} from '@/common/craft-global';

// `new Craft.PromptHandler()` is created by the legacy `Craft.AssetIndex`.
registerCraftGlobals({PromptHandler});
