import {FileUpload, UploadError, registerTransport} from './upload-client';
import {defaults, initUploadForms} from './upload-forms';
import {registerCraftGlobals} from '@/common/craft-global';

export {FileUpload, UploadError, registerTransport, defaults};
export type {
  PrepareUpload,
  UploadOptions,
  UploadState,
  UploadTransport,
  UploadTransportContext,
} from './upload-client';
export type {UploadDefaults} from './upload-forms';

// The standalone entry for pages outside the control panel, which don't get the
// `@craftcms/cp` import map. Control panel code imports `Uploads` from there.
const Uploads = {FileUpload, UploadError, registerTransport, defaults};
registerCraftGlobals({Uploads});
initUploadForms();
