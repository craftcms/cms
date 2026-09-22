import {actionClient, getActionUrl} from '@craftcms/ui';

export type ConflictResolution = 'keepBoth' | 'replace' | 'cancel';

export interface AssetMoveConflict {
  assetId: number;
  /** The filename that clashes at the destination. */
  filename: string;
  /** A non-clashing "keep both" name the server suggests. */
  suggestedFilename: string;
}

export interface MoveAssetsResult {
  moved: number;
  cancelled: number;
}

export type FolderConflictResolution = 'replace' | 'merge' | 'cancel';

interface MoveFolderResponse {
  conflict?: string;
  transferList?: Array<Record<string, unknown>>;
  newFolderUrl?: string;
}

export interface MoveFoldersResult extends MoveAssetsResult {
  movedFolderUrls: string[];
}

interface MoveAssetParams {
  assetId: number;
  folderId: number;
  filename?: string;
  force?: 1;
}

interface MoveAssetResponse {
  conflict?: string;
  filename?: string;
  suggestedFilename?: string;
}

async function moveOne(params: MoveAssetParams): Promise<MoveAssetResponse> {
  const {data} = await actionClient.post<MoveAssetResponse>(
    getActionUrl('assets/move-asset'),
    params
  );
  return data ?? {};
}

/**
 * Moves assets into a target folder — one `assets/move-asset` request per asset,
 * matching Craft 5's `Craft.AssetMover`.
 *
 * A filename clash at the destination comes back as an HTTP 200 response
 * carrying a `conflict` key (NOT an error status), so the body is inspected
 * rather than the status. `resolveConflict` decides keep-both / replace /
 * cancel, and the move is re-issued with `filename` (keep both) or `force`
 * (replace/merge).
 */
export async function moveAssets(
  assetIds: Array<number>,
  targetFolderId: number,
  resolveConflict: (conflict: AssetMoveConflict) => Promise<ConflictResolution>
): Promise<MoveAssetsResult> {
  let moved = 0;
  let cancelled = 0;

  for (const assetId of assetIds) {
    const params: MoveAssetParams = {assetId, folderId: targetFolderId};
    let data = await moveOne(params);

    if (data.conflict) {
      if (!data.filename || !data.suggestedFilename) {
        throw new Error('The asset conflict response is missing filenames.');
      }
      const choice = await resolveConflict({
        assetId,
        filename: data.filename,
        suggestedFilename: data.suggestedFilename,
      });

      if (choice === 'cancel') {
        cancelled++;
        continue;
      }

      data = await moveOne(
        choice === 'replace'
          ? {...params, force: 1}
          : {...params, filename: data.suggestedFilename}
      );

      // A forced/renamed move shouldn't clash again; if it somehow does, count
      // it as cancelled rather than looping.
      if (data.conflict) {
        cancelled++;
        continue;
      }
    }

    moved++;
  }

  return {moved, cancelled};
}

export async function moveFolders(
  folderIds: number[],
  targetFolderId: number,
  resolveConflict: (message: string) => Promise<FolderConflictResolution>
): Promise<MoveFoldersResult> {
  let moved = 0;
  let cancelled = 0;
  const movedFolderUrls: string[] = [];

  for (const folderId of folderIds) {
    const params: Record<string, unknown> = {
      folderId,
      parentId: targetFolderId,
    };
    let {data} = await actionClient.post<MoveFolderResponse>(
      getActionUrl('assets/move-folder'),
      params
    );

    if (data.conflict) {
      const choice = await resolveConflict(data.conflict);

      if (choice === 'cancel') {
        cancelled++;
        continue;
      }

      params[choice === 'replace' ? 'force' : 'merge'] = true;
      ({data} = await actionClient.post<MoveFolderResponse>(
        getActionUrl('assets/move-folder'),
        params
      ));
    }

    if (!Array.isArray(data.transferList) || !data.newFolderUrl) {
      throw new Error('The folder move response is incomplete.');
    }

    for (const transfer of data.transferList) {
      const response = await actionClient.post<MoveAssetResponse>(
        getActionUrl('assets/move-asset'),
        transfer
      );

      if (response.data && 'conflict' in response.data) {
        throw new Error(
          response.data.conflict || 'An asset could not be moved.'
        );
      }
    }

    await actionClient.post(getActionUrl('assets/delete-folder'), {folderId});
    movedFolderUrls.push(data.newFolderUrl);
    moved++;
  }

  return {moved, cancelled, movedFolderUrls};
}
