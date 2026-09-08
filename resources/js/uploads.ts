import {FileUpload, UploadError} from './upload-client';

export {FileUpload, UploadError};
export type {UploadOptions, UploadState} from './upload-client';

declare global {
  interface Window {
    CraftUploads: {
      FileUpload: typeof FileUpload;
      UploadError: typeof UploadError;
    };
  }
}

window.CraftUploads = {FileUpload, UploadError};
