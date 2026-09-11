import {FileUpload, UploadError, registerTransport} from './upload-client';
import {registerCraftGlobals} from '@/common/craft-global';

export {FileUpload, UploadError, registerTransport};
export type {
  UploadOptions,
  UploadState,
  UploadTransport,
  UploadTransportContext,
} from './upload-client';

const Uploads = {FileUpload, UploadError, registerTransport};
registerCraftGlobals({Uploads});
