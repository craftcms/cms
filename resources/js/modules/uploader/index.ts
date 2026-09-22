import '@/uploads';
import {BaseUploader} from './base-uploader';
import {Uploader} from './uploader';
import {registerCraftGlobals} from '@/common/craft-global';

const Uploaders = {BaseUploader, Uploader};
registerCraftGlobals({Uploaders});

export {BaseUploader, Uploader};
