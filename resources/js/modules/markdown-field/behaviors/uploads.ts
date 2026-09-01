import {t} from '@craftcms/ui';
import {useHttp} from '@inertiajs/vue3';
import type {Options} from 'overtype';
import {upload as uploadAsset} from '@actions/Assets/UploadController';
import {useFlashMessages} from '@/common/composables/useFlashMessages';
import {escapeMarkdownLabel} from './utilities';
import axios from 'axios';

const ASSET_REF_HANDLE = 'asset';

type AssetUploadResponse = {
  assetId?: number | string;
  filename?: string;
  message?: string;
};

type UploadRequest = {
  'assets-upload': File;
  folderId: string;
};

interface UploadErrorEnvelope {
  response?: {data?: string};
}

const {flash} = useFlashMessages();

export function fileUploadOptions(
  uploadFolderId: number | null,
  uploadSiteId: number | string
): NonNullable<Options['fileUpload']> | undefined {
  if (!uploadFolderId) {
    return undefined;
  }

  return {
    batch: false,
    enabled: true,
    // OverType defaults to 10 MB when omitted; keep this high so PHP validates uploads.
    maxSize: Number.MAX_SAFE_INTEGER,
    onInsertFile: (file) => {
      const upload = Array.isArray(file) ? file[0] : file;
      if (!upload) {
        throw new Error('No file was selected for upload.');
      }
      return uploadFile(upload, uploadFolderId, uploadSiteId);
    },
  };
}

async function uploadFile(
  file: File,
  uploadFolderId: number,
  uploadSiteId: number | string
): Promise<string> {
  const uploadRequest = useHttp<UploadRequest, AssetUploadResponse>({
    'assets-upload': file,
    folderId: uploadFolderId.toString(),
  });

  try {
    const data = await uploadRequest.post(uploadAsset().url);

    return uploadedAssetMarkdown(file, data, uploadSiteId);
  } catch (error) {
    flash(
      'error',
      uploadErrorMessage(
        error instanceof Object ? error : new Error(String(error))
      )
    );

    throw error;
  }
}

function uploadedAssetMarkdown(
  file: File,
  response: AssetUploadResponse,
  uploadSiteId: number | string
): string {
  if (!response.assetId) {
    throw new Error(response.message || t('Couldn’t upload file.'));
  }

  const label = escapeMarkdownLabel(response.filename || file.name);
  const ref = `{${ASSET_REF_HANDLE}:${response.assetId}@${uploadSiteId}:url}`;

  return file.type.startsWith('image/')
    ? `![${label}](${ref})`
    : `[${label || ref}](${ref})`;
}

function uploadErrorMessage(error: Error | UploadErrorEnvelope): string {
  const responseData = axios.isAxiosError<string>(error)
    ? error.response?.data
    : undefined;
  const responseMessage =
    Object(responseData).constructor === String
      ? parseUploadErrorMessage(String(responseData))
      : undefined;

  if (responseMessage) {
    return responseMessage;
  }

  return error instanceof Error ? error.message : t('Couldn’t upload file.');
}

function parseUploadErrorMessage(responseData: string): string | undefined {
  try {
    // SAFETY: The upload error endpoint serializes an object with an optional message string.
    const data = JSON.parse(responseData) as {message?: string};

    return data.message;
  } catch {
    return undefined;
  }
}
