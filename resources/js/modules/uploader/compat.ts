import {BaseUploader} from './base-uploader';
import {Uploader} from './uploader';
import {registerCraftGlobals} from '@/common/craft-global';

// The legacy asset index uploads through `Craft.Uploaders.Uploader`. Core
// imports the classes.
const Uploaders = {BaseUploader, Uploader};
registerCraftGlobals({Uploaders});
