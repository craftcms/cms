import {t} from '@craftcms/ui';
import type {Options} from 'overtype';
import {store} from '@/routes/craft/actions/craft/cp/uploads';
import {AssetUpload, type UploadResult} from '@/upload-client';
import {useFlashMessages} from '@/common/composables/useFlashMessages';
import {escapeMarkdownLabel} from './utilities';

const ASSET_REF_HANDLE = 'asset';

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
    // Let the upload session enforce the configured asset size limit.
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
  const task = new AssetUpload(file, {
    url: store.url(),
    parameters: {folderId: uploadFolderId},
  });

  try {
    const data = await task.upload();

    return uploadedAssetMarkdown(file, data, uploadSiteId);
  } catch (error) {
    flash(
      'error',
      error instanceof Error ? error.message : t('Couldn’t upload file.')
    );

    throw error;
  }
}

function uploadedAssetMarkdown(
  file: File,
  response: UploadResult,
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
