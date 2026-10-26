import {registerCraftGlobals} from '@/common/craft-global';
import {
  AssetImageEditor,
  openAssetImageEditor,
} from './open-asset-image-editor';

/**
 * `Craft.openAssetImageEditor` is how markup that isn't an Inertia page opens
 * the image editor dialog. `Craft.AssetImageEditor` is the legacy editor's
 * name, kept for plugins.
 */
registerCraftGlobals({openAssetImageEditor, AssetImageEditor});

export {
  AssetImageEditor,
  openAssetImageEditor,
} from './open-asset-image-editor';
