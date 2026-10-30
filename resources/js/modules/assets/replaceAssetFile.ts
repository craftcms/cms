import {t} from '@craftcms/ui';
import {Uploader} from '@/modules/uploader/uploader';
import type {UploaderCallbacks} from '@/modules/uploader/base-uploader';
import {broadcast} from '@/common/craft/broadcast';
import {showError, showNotice} from '@/modules/messages/messages';

/**
 * Swaps the asset's file for a newly uploaded one through the native uploader.
 * `onReplaced` should refresh whatever shows the asset, so the filename, size,
 * dimensions, and thumbnail all come back from the server together.
 */
export function replaceAssetFile(
  assetId: number,
  onReplaced: () => void
): void {
  const input = document.createElement('input');
  input.type = 'file';
  input.name = 'replaceFile';
  input.hidden = true;
  document.body.append(input);

  const uploaderSettings = {
    dropZone: null,
    fileInput: input,
    paramName: 'replaceFile',
    replace: true,
  };
  Object.assign(uploaderSettings, {
    on: {
      done: ({result}) => {
        if (result?.error) {
          showError(result.error);

          return;
        }

        showNotice(t('New file uploaded.'));
        broadcast({event: 'saveElement', id: assetId});
        onReplaced();
      },
      fail: ({error, canceled}) => {
        if (!canceled) {
          showError(
            error instanceof Error ? error.message : t('Replace file failed.')
          );
        }
      },
      settled: () => input.remove(),
    } satisfies UploaderCallbacks,
  });
  const uploader = new Uploader(input, uploaderSettings);

  uploader.setParams({assetId});
  input.click();
}
