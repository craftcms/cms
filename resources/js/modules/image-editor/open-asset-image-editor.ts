import {actionClient} from '@craftcms/ui';
import {refreshPreviews} from '@/modules/elements/live-preview/previews';
import {show} from '@actions/Assets/ImageEditorController';
import {
  openImageEditorDialog,
  type ImageEditorSettings,
} from './open-image-editor-dialog';
import type {SaveResult} from './useImageEditor';

/**
 * Opens the image editor dialog from markup that isn't an Inertia page — asset
 * chips' and cards' “Open in Image Editor” items, relation fields, and the
 * legacy element index action — given the asset's ID, or its editor settings
 * when the server has already sent them.
 *
 * Inertia callers reload after a save; these can't, so a save that replaced
 * the file tells other tabs, runs the queue, and refreshes Live Preview.
 */
export async function openAssetImageEditor(
  asset: number | ImageEditorSettings,
  onSaved?: (result: SaveResult) => void
): Promise<void> {
  const settings =
    typeof asset === 'number'
      ? (
          await actionClient.post<ImageEditorSettings>(show.url(), {
            assetId: asset,
          })
        ).data
      : asset;

  await openImageEditorDialog(settings, (result) => {
    if (!result.newAssetId) {
      Craft.broadcaster?.postMessage({
        event: 'saveElement',
        id: settings.assetId,
      });
    }

    Craft.cp?.runQueue?.();
    refreshPreviews();
    onSaved?.(result);
  });
}

/**
 * @deprecated `Craft.openAssetImageEditor()` should be used instead. Kept so
 * plugins that construct the legacy editor open the dialog instead.
 */
export class AssetImageEditor {
  constructor(
    assetId: number,
    settings: {onSave?: (result: SaveResult) => void} = {}
  ) {
    openAssetImageEditor(assetId, settings.onSave).catch((error) =>
      Craft.cp?.displayError?.(error?.response?.data?.message)
    );
  }
}
