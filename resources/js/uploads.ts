import {FileUpload, UploadError, registerTransport} from './upload-client';
import {registerCraftGlobals} from '@/common/craft-global';

export {FileUpload, UploadError, registerTransport};
export type {
  PrepareUpload,
  UploadOptions,
  UploadState,
  UploadTransport,
  UploadTransportContext,
} from './upload-client';

// The standalone entry for pages outside the control panel, which don't get the
// `@craftcms/cp` import map. Control panel code imports `Uploads` from there.
const Uploads = {FileUpload, UploadError, registerTransport};
registerCraftGlobals({Uploads});
