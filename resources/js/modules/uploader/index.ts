import {BaseUploader} from './base-uploader';
import {Uploader} from './uploader';
import {registerCraftGlobals} from '@/common/craft-global';

// `Craft.createUploader`/`registerUploaderClass` (a registry seam still in the
// legacy bundle) resolve these globals, and plugins may extend `Craft.Uploader`.
registerCraftGlobals({BaseUploader, Uploader});

export {BaseUploader, Uploader};
