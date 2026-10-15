import {beforeEach, expect, it, vi} from 'vite-plus/test';
import {moveAssets, moveFolders} from './assetMover';

const post = vi.hoisted(() => vi.fn());

vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal()),
  actionClient: {post},
  getActionUrl: (action: string) => `/actions/${action}`,
}));

beforeEach(() => {
  post.mockReset();
});

it('moves a folder’s assets before deleting its old tree', async () => {
  post
    .mockResolvedValueOnce({
      data: {
        transferList: [{assetId: 7, folderId: 30, filename: 'photo.jpg'}],
        newFolderId: 31,
        newFolderUrl: '/admin/assets/photos/moved',
      },
    })
    .mockResolvedValueOnce({data: {}})
    .mockResolvedValueOnce({data: {}});

  await expect(moveFolders([11], 30, async () => 'cancel')).resolves.toEqual({
    moved: 1,
    cancelled: 0,
    conflicted: 0,
    movedFolders: [{folderId: 11, newFolderId: 31}],
    movedFolderUrls: ['/admin/assets/photos/moved'],
  });

  expect(post.mock.calls).toEqual([
    ['/actions/assets/move-folder', {folderId: 11, parentId: 30}],
    [
      '/actions/assets/move-asset',
      {assetId: 7, folderId: 30, filename: 'photo.jpg'},
    ],
    ['/actions/assets/delete-folder', {folderId: 11}],
  ]);
});

it('retries a conflicting folder move with the chosen resolution', async () => {
  post
    .mockResolvedValueOnce({data: {conflict: 'Already exists'}})
    .mockResolvedValueOnce({
      data: {
        transferList: [],
        newFolderId: 32,
        newFolderUrl: '/admin/assets/photos/merged',
      },
    })
    .mockResolvedValueOnce({data: {}});

  await expect(moveFolders([11], 30, async () => 'merge')).resolves.toEqual({
    moved: 1,
    cancelled: 0,
    conflicted: 1,
    movedFolders: [{folderId: 11, newFolderId: 32}],
    movedFolderUrls: ['/admin/assets/photos/merged'],
  });

  expect(post.mock.calls[1]).toEqual([
    '/actions/assets/move-folder',
    {folderId: 11, parentId: 30, merge: true},
  ]);
});

it('keeps the source folder when an asset transfer reports a conflict', async () => {
  post
    .mockResolvedValueOnce({
      data: {
        transferList: [{assetId: 7, folderId: 30, filename: 'photo.jpg'}],
        newFolderId: 31,
        newFolderUrl: '/admin/assets/photos/moved',
      },
    })
    .mockResolvedValueOnce({data: {conflict: 'Already exists'}});

  await expect(moveFolders([11], 30, async () => 'cancel')).rejects.toThrow(
    'Already exists'
  );

  expect(post).not.toHaveBeenCalledWith('/actions/assets/delete-folder', {
    folderId: 11,
  });
});

it('keeps the source folder when the move response is incomplete', async () => {
  post.mockResolvedValueOnce({data: {newFolderUrl: '/admin/assets/moved'}});

  await expect(moveFolders([11], 30, async () => 'cancel')).rejects.toThrow(
    'The folder move response is incomplete.'
  );

  expect(post).toHaveBeenCalledOnce();
});

it('reports which assets moved and how many needed a conflict resolved', async () => {
  post
    .mockResolvedValueOnce({data: {}})
    .mockResolvedValueOnce({
      data: {
        conflict: 'Already exists',
        filename: 'b.jpg',
        suggestedFilename: 'b_1.jpg',
      },
    })
    .mockResolvedValueOnce({data: {}})
    .mockResolvedValueOnce({
      data: {
        conflict: 'Already exists',
        filename: 'c.jpg',
        suggestedFilename: 'c_1.jpg',
      },
    });

  const choices = ['keepBoth', 'cancel'] as const;
  let choice = 0;

  await expect(
    moveAssets([1, 2, 3], 30, async () => choices[choice++]!)
  ).resolves.toEqual({
    moved: 2,
    cancelled: 1,
    conflicted: 1,
    movedAssetIds: [1, 2],
  });
});
