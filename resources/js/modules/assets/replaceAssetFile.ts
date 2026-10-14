import {t} from '@craftcms/ui';
import {Uploader} from '@/modules/uploader/uploader';
import type {UploaderCallbacks} from '@/modules/uploader/base-uploader';

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
          Craft.cp?.displayError?.(result.error);

          return;
        }

        Craft.cp?.displayNotice?.(t('New file uploaded.'));
        Craft.broadcaster?.postMessage({event: 'saveElement', id: assetId});
        onReplaced();
      },
      fail: ({error, canceled}) => {
        if (!canceled) {
          Craft.cp?.displayError?.(
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
