import {beforeEach, expect, it, vi} from 'vite-plus/test';
import {moveFolders} from './assetMover';

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
        newFolderUrl: '/admin/assets/photos/moved',
      },
    })
    .mockResolvedValueOnce({data: {}})
    .mockResolvedValueOnce({data: {}});

  await expect(moveFolders([11], 30, async () => 'cancel')).resolves.toEqual({
    moved: 1,
    cancelled: 0,
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
      data: {transferList: [], newFolderUrl: '/admin/assets/photos/merged'},
    })
    .mockResolvedValueOnce({data: {}});

  await expect(moveFolders([11], 30, async () => 'merge')).resolves.toEqual({
    moved: 1,
    cancelled: 0,
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
