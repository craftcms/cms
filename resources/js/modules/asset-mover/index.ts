import {AssetMover} from './asset-mover';
import {registerCraftGlobals} from '@/common/craft-global';

// `new Craft.AssetMover()` is created by the asset index. Plain modern ES class.
registerCraftGlobals({AssetMover});

export {AssetMover};
