import {AssetUpload, UploadError} from './upload-client';

export {AssetUpload, UploadError};
export type {UploadOptions, UploadResult, UploadState} from './upload-client';

declare global {
  interface Window {
    CraftUploads: {
      AssetUpload: typeof AssetUpload;
      UploadError: typeof UploadError;
    };
  }
}

window.CraftUploads = {AssetUpload, UploadError};
