import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {effectScope} from 'vue';
import {useAssetFolderActions} from './useAssetFolderActions';
import {useElementIndexTable} from '@/modules/elements/composables/useElementIndexTable';

const post = vi.hoisted(() => vi.fn());
const visit = vi.hoisted(() => vi.fn());
const VolumeFolderSelectorModal = vi.hoisted(() => vi.fn());
const onActionPerformed = vi.fn();

vi.mock('@craftcms/ui', () => ({
  actionClient: {post},
  getActionUrl: (action: string) => `/actions/${action}`,
  t: (message: string, params?: Record<string, string>) =>
    params
      ? Object.entries(params).reduce(
          (translated, [key, value]) => translated.replace(`{${key}}`, value),
          message
        )
      : message,
}));

vi.mock('@inertiajs/vue3', () => ({
  router: {visit},
}));

vi.mock(
  '@/modules/element-selector-modal/volume-folder-selector-modal',
  () => ({VolumeFolderSelectorModal})
);

const {register} = useElementIndexTable();
let scope: ReturnType<typeof effectScope>;
let actions: ReturnType<typeof useAssetFolderActions>;

beforeEach(() => {
  post.mockReset();
  visit.mockReset();
  onActionPerformed.mockReset();
  VolumeFolderSelectorModal.mockReset();
  VolumeFolderSelectorModal.mockImplementation(function (this: any) {
    this.hide = vi.fn();
  });
  vi.stubGlobal('Craft', {
    cp: {
      displayNotice: vi.fn(),
      displayNotification: vi.fn(),
      displayError: vi.fn(),
    },
  });
  scope = effectScope();
  register({
    table: {
      getRow: (id: string) => ({
        original: {folderName: id === 'folder:7' ? 'Product Photos' : null},
      }),
    } as any,
    onActionPerformed,
    refreshResults: vi.fn(),
  });
  scope.run(() => {
    actions = useAssetFolderActions();
  });
});

afterEach(() => {
  scope.stop();
  register(null);
  vi.restoreAllMocks();
  vi.unstubAllGlobals();
});

it('creates a subfolder in the requested parent folder', async () => {
  post.mockResolvedValueOnce({data: {}});

  window.dispatchEvent(
    new CustomEvent('assets:new-subfolder', {detail: {folderId: 3}})
  );
  actions.newFolderName.value = ' Product Photos ';
  await actions.createSubfolder();

  expect(post).toHaveBeenCalledWith('/actions/assets/create-folder', {
    parentId: 3,
    folderName: 'Product Photos',
  });
  expect(actions.newFolderOpen.value).toBe(false);
  expect(onActionPerformed).toHaveBeenCalledOnce();
});

it('keeps create and rename failures in their open dialogs', async () => {
  post.mockRejectedValueOnce({
    isAxiosError: true,
    response: {data: {message: 'A folder with that name already exists.'}},
  });
  window.dispatchEvent(
    new CustomEvent('assets:new-subfolder', {detail: {folderId: 3}})
  );
  actions.newFolderName.value = 'Product Photos';

  await actions.createSubfolder();

  expect(actions.newFolderOpen.value).toBe(true);
  expect(actions.newFolderError.value).toBe(
    'A folder with that name already exists.'
  );

  post.mockRejectedValueOnce({
    isAxiosError: true,
    response: {data: {message: 'The folder could not be renamed.'}},
  });
  window.dispatchEvent(
    new CustomEvent('assets:rename-folders', {
      detail: {folderIds: [7], label: 'Product Photos'},
    })
  );

  await actions.submitRename();

  expect(actions.renameFolderId.value).toBe(7);
  expect(actions.renameError.value).toBe('The folder could not be renamed.');
});

it('names a selected folder in the delete confirmation', () => {
  const confirm = vi.fn(() => false);
  vi.stubGlobal('confirm', confirm);

  window.dispatchEvent(
    new CustomEvent('assets:delete-folders', {
      detail: {elementIds: ['folder:7']},
    })
  );

  expect(confirm).toHaveBeenCalledWith(
    'Really delete folder “Product Photos”?'
  );
  expect(post).not.toHaveBeenCalled();
});

it('opens the move picker at the old parent and returns there after moving', async () => {
  post
    .mockResolvedValueOnce({
      data: {
        transferList: [],
        newFolderUrl: '/cp/assets/other/managed',
      },
    })
    .mockResolvedValueOnce({data: {}});

  window.dispatchEvent(
    new CustomEvent('assets:move-folders', {
      detail: {
        folderIds: [7],
        sources: ['volume:abc'],
        defaultSource: 'volume:abc',
        defaultSourcePath: [{folderId: 3, label: 'Parent'}],
        disabledFolderIds: [3],
        redirectUrl: '/cp/assets/photos',
      },
    })
  );

  expect(VolumeFolderSelectorModal).toHaveBeenCalledOnce();
  const options = VolumeFolderSelectorModal.mock.calls[0]![0];
  expect(options).toMatchObject({
    sources: ['volume:abc'],
    modalTitle: 'Move to',
    selectBtnLabel: 'Move',
    defaultSource: 'volume:abc',
    defaultSourcePath: [{folderId: 3, label: 'Parent'}],
    disabledFolderIds: [7, 3],
  });

  await options.onSelect([{folderId: 12}]);

  expect(
    VolumeFolderSelectorModal.mock.results[0]!.value.hide
  ).toHaveBeenCalled();
  expect(post).toHaveBeenCalledWith('/actions/assets/move-folder', {
    folderId: 7,
    parentId: 12,
  });
  expect(visit).toHaveBeenCalledWith('/cp/assets/photos');
});
