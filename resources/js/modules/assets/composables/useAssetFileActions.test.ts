import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {effectScope} from 'vue';
import {useAssetFileActions} from './useAssetFileActions';

const post = vi.hoisted(() => vi.fn());
const VolumeFolderSelectorModal = vi.hoisted(() => vi.fn());
const performMove = vi.fn(async () => {});
const refresh = vi.fn(async () => {});

vi.mock('@craftcms/ui', async () => ({
  actionClient: {post},
  isHttpError: (await import('@craftcms/ui/utilities/api/http')).isHttpError,
  getActionUrl: (action: string) => `/actions/${action}`,
  t: (message: string) => message,
}));

vi.mock(
  '@/modules/element-selector-modal/volume-folder-selector-modal',
  () => ({VolumeFolderSelectorModal})
);

let scope: ReturnType<typeof effectScope>;
let actions: ReturnType<typeof useAssetFileActions>;
let createCopyTextPrompt: ReturnType<typeof vi.fn>;

beforeEach(() => {
  post.mockReset();
  performMove.mockClear();
  refresh.mockClear();
  VolumeFolderSelectorModal.mockReset();
  VolumeFolderSelectorModal.mockImplementation(function (this: any) {
    this.hide = vi.fn();
  });
  createCopyTextPrompt = vi.fn();
  vi.stubGlobal('Craft', {
    cp: {displayError: vi.fn(), runQueue: vi.fn()},
    ui: {createCopyTextPrompt},
  });
  scope = effectScope();
  scope.run(() => {
    actions = useAssetFileActions(
      {
        findRow: (id) => ({
          id,
          label: '',
          assetFilename: 'photo.jpg',
          viewUrl: 'https://example.test/photo.jpg',
        }),
        clearSelection: () => {},
        refresh,
        captureSelection: () => () => {},
        view: {} as never,
      },
      performMove
    );
  });
});

afterEach(() => {
  scope.stop();
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

function dispatch(name: string, detail: Record<string, unknown>) {
  window.dispatchEvent(new CustomEvent(name, {detail}));
}

it('renames a file, keeping the dialog open on a conflict', async () => {
  dispatch('assets:rename-file', {elementIds: [5]});

  expect(actions.renameAssetId.value).toBe(5);
  expect(actions.renameFilename.value).toBe('photo.jpg');

  actions.renameFilename.value = 'taken.jpg';
  post.mockResolvedValueOnce({
    data: {conflict: 'A file named “taken.jpg” already exists.'},
  });
  await actions.submitRenameFile();

  expect(post).toHaveBeenCalledWith('/actions/assets/move-asset', {
    assetId: 5,
    filename: 'taken.jpg',
  });
  expect(actions.renameFileError.value).toBe(
    'A file named “taken.jpg” already exists.'
  );
  expect(refresh).not.toHaveBeenCalled();

  actions.renameFilename.value = 'new.jpg';
  post.mockResolvedValueOnce({data: {success: true}});
  await actions.submitRenameFile();

  expect(actions.renameAssetId.value).toBeNull();
  expect(refresh).toHaveBeenCalled();
});

it('moves the selected files through the shared move', async () => {
  dispatch('assets:move-assets', {
    elementIds: [5, 'folder:3', 6],
    sources: ['volume:a'],
    disabledFolderIds: [2],
  });

  const settings = VolumeFolderSelectorModal.mock.calls[0]![0];
  expect(settings.sources).toEqual(['volume:a']);
  expect(settings.disabledFolderIds).toEqual([2]);

  await settings.onSelect([{folderId: '9'}]);

  expect(performMove).toHaveBeenCalledWith(9, [5, 6], []);
});

it('offers the file’s URL and reference tag for copying', () => {
  dispatch('assets:copy-url', {elementIds: [5]});
  dispatch('assets:copy-reference-tag', {elementIds: [5]});

  expect(createCopyTextPrompt).toHaveBeenNthCalledWith(1, {
    label: 'Copy the URL',
    value: 'https://example.test/photo.jpg',
  });
  expect(createCopyTextPrompt).toHaveBeenNthCalledWith(2, {
    label: 'Copy the reference tag',
    value: '{asset:5}',
  });
});
