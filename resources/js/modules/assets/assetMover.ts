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
  /** Clashes settled by keeping both or replacing, which can't be undone. */
  conflicted: number;
}

export interface MovedAssetsResult extends MoveAssetsResult {
  movedAssetIds: number[];
}

export type FolderConflictResolution = 'replace' | 'merge' | 'cancel';

interface MoveFolderResponse {
  conflict?: string;
  transferList?: Array<Record<string, unknown>>;
  newFolderId?: number | string;
  newFolderUrl?: string;
}

export interface MovedFolder {
  folderId: number;
  /** The folder's ID at its new location; moving a folder recreates it. */
  newFolderId: number;
}

export interface MoveFoldersResult extends MoveAssetsResult {
  movedFolders: MovedFolder[];
  movedFolderUrls: string[];
}

export interface MoveInfo {
  count: number;
  totalSize: number;
}

/** The number of items that can be moved before the move needs confirming. */
export const MOVE_COUNT_LIMIT = 50;

/** The total size of assets (50MB) that can be moved before the move needs confirming. */
export const MOVE_SIZE_LIMIT = 50000000;

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
 * Counts the assets a move would touch, including those inside the moved
 * folders, and their total size.
 */
export async function getMoveInfo(
  folderIds: number[],
  assetIds: number[]
): Promise<MoveInfo> {
  const {data} = await actionClient.post<MoveInfo>(
    getActionUrl('assets/move-info'),
    {folderIds, assetIds}
  );
  return data;
}

/**
 * Whether a move is large enough that it needs confirming first, and can't be
 * undone afterwards.
 */
export function isLargeMove(info: MoveInfo): boolean {
  return info.count > MOVE_COUNT_LIMIT || info.totalSize > MOVE_SIZE_LIMIT;
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
): Promise<MovedAssetsResult> {
  let moved = 0;
  let cancelled = 0;
  let conflicted = 0;
  const movedAssetIds: number[] = [];

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
      // it as canceled rather than looping.
      if (data.conflict) {
        cancelled++;
        continue;
      }

      conflicted++;
    }

    moved++;
    movedAssetIds.push(assetId);
  }

  return {moved, cancelled, conflicted, movedAssetIds};
}

export async function moveFolders(
  folderIds: number[],
  targetFolderId: number,
  resolveConflict: (message: string) => Promise<FolderConflictResolution>
): Promise<MoveFoldersResult> {
  let moved = 0;
  let cancelled = 0;
  let conflicted = 0;
  const movedFolders: MovedFolder[] = [];
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
      conflicted++;
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
    const newFolderId = Number(data.newFolderId);
    if (Number.isFinite(newFolderId)) {
      movedFolders.push({folderId, newFolderId});
    }
    movedFolderUrls.push(data.newFolderUrl);
    moved++;
  }

  return {moved, cancelled, conflicted, movedFolders, movedFolderUrls};
}
