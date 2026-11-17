import {openAssetImageEditor} from './open-asset-image-editor';
import {registerCraftGlobals} from '@/common/craft-global';

/**
 * `Craft.openAssetImageEditor` is how markup that isn't an Inertia page opens
 * the image editor dialog.
 */
registerCraftGlobals({openAssetImageEditor});
