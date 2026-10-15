import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {createApp, defineComponent, h} from 'vue';
import {
  getMoveInfo,
  moveAssets,
  moveFolders,
} from '@/modules/assets/assetMover';
import {useAssetMoveDrag} from './useAssetMoveDrag';

vi.mock('@/modules/assets/assetMover', () => ({
  getMoveInfo: vi.fn(),
  isLargeMove: (info: {count: number; totalSize: number}) =>
    info.count > 50 || info.totalSize > 50000000,
  moveAssets: vi.fn(),
  moveFolders: vi.fn(),
}));

let app: ReturnType<typeof createApp> | null = null;
let container: HTMLElement | null = null;

const displayNotification = vi.fn();
const close = vi.fn();
const confirm = vi.fn();

beforeEach(() => {
  vi.mocked(getMoveInfo)
    .mockReset()
    .mockResolvedValue({count: 1, totalSize: 1});
  vi.mocked(moveAssets)
    .mockReset()
    .mockImplementation(async (assetIds) => ({
      moved: assetIds.length,
      cancelled: 0,
      conflicted: 0,
      movedAssetIds: assetIds,
    }));
  vi.mocked(moveFolders)
    .mockReset()
    .mockImplementation(async (folderIds) => ({
      moved: folderIds.length,
      cancelled: 0,
      conflicted: 0,
      movedFolders: folderIds.map((folderId) => ({
        folderId,
        newFolderId: folderId + 100,
      })),
      movedFolderUrls: [],
    }));
  displayNotification.mockReset().mockReturnValue({close});
  close.mockReset();
  confirm.mockReset();
  vi.stubGlobal('confirm', confirm);
  vi.stubGlobal('Craft', {cp: {displayNotification, displayError: vi.fn()}});
});

afterEach(() => {
  app?.unmount();
  app = null;
  container?.remove();
  container = null;
  vi.unstubAllGlobals();
});

const rows: Record<string, Record<string, unknown>> = {
  '5': {id: 5, folderId: 1},
  '6': {id: 6, folderId: 2},
  '7': {id: 7, folderId: 2},
  'folder:9': {id: 'folder:9', isFolder: true, folderId: 9, parentFolderId: 1},
};

function mountMoveDrag() {
  let moveDrag!: ReturnType<typeof useAssetMoveDrag>;
  const Component = defineComponent({
    setup() {
      moveDrag = useAssetMoveDrag({
        findRow: (id: string | number) => rows[String(id)],
        clearSelection: () => {},
        refresh: async () => {},
        captureSelection: () => () => {},
        view: {selection: {selectedIds: {value: []}}} as never,
      } as never);

      return () => h('div', {class: 'element-index'});
    },
  });

  container = document.createElement('div');
  document.body.append(container);
  app = createApp(Component);
  app.mount(container);

  return moveDrag;
}

function undoButton(): HTMLElement | undefined {
  return displayNotification.mock.calls.at(-1)?.[2]?.details;
}

it('keeps asset links clickable without disabling row dragging', () => {
  const Component = defineComponent({
    setup() {
      useAssetMoveDrag({
        findRow: () => undefined,
        clearSelection: () => {},
        refresh: async () => {},
        captureSelection: () => () => {},
        view: {selection: {selectedIds: {value: []}}} as never,
      });

      return () =>
        h('div', {class: 'element-index'}, [
          h('div', {class: 'element-index__body'}, [
            h('div', {'data-movable-item': '', 'data-row-id': '42'}, [
              h('a', {href: '/admin/assets/edit/42-example'}, 'Example'),
              h('span', 'Filename'),
            ]),
          ]),
        ]);
    },
  });

  container = document.createElement('div');
  document.body.append(container);
  app = createApp(Component);
  app.mount(container);

  const linkEvent = new PointerEvent('pointerdown', {
    bubbles: true,
    cancelable: true,
    button: 0,
    isPrimary: true,
  });
  container.querySelector('a')!.dispatchEvent(linkEvent);

  const rowEvent = new PointerEvent('pointerdown', {
    bubbles: true,
    cancelable: true,
    button: 0,
    isPrimary: true,
  });
  container.querySelector('span')!.dispatchEvent(rowEvent);

  expect(linkEvent.defaultPrevented).toBe(false);
  expect(rowEvent.defaultPrevented).toBe(true);
});

it('offers to undo a move', async () => {
  const {performMove} = mountMoveDrag();

  await performMove(30, [5], []);

  expect(displayNotification).toHaveBeenCalledWith('notice', 'Item moved.', {
    details: expect.any(HTMLElement),
  });
  expect(undoButton()?.textContent).toBe('Undo');
});

it('leaves the move alone when undoing it isn’t confirmed', async () => {
  const {performMove} = mountMoveDrag();
  await performMove(30, [5], []);
  vi.mocked(moveAssets).mockClear();
  confirm.mockReturnValue(false);

  undoButton()!.click();
  await vi.waitFor(() => expect(close).toHaveBeenCalled());

  expect(confirm).toHaveBeenCalledWith(
    'Are you sure you want to undo the move?'
  );
  expect(moveAssets).not.toHaveBeenCalled();
});

it('moves each item back to the folder it came from', async () => {
  const {performMove} = mountMoveDrag();
  await performMove(30, [5, 6, 7], [9]);
  vi.mocked(moveAssets).mockClear();
  vi.mocked(moveFolders).mockClear();
  confirm.mockReturnValue(true);

  undoButton()!.click();
  await vi.waitFor(() =>
    expect(displayNotification).toHaveBeenLastCalledWith(
      'notice',
      'Move reverted.'
    )
  );

  expect(
    vi.mocked(moveFolders).mock.calls.map((call) => call.slice(0, 2))
  ).toEqual([[[109], 1]]);
  expect(
    vi.mocked(moveAssets).mock.calls.map((call) => call.slice(0, 2))
  ).toEqual([
    [[5], 1],
    [[6, 7], 2],
  ]);
  expect(close).toHaveBeenCalled();
});

it('doesn’t offer to undo a move that resolved a conflict', async () => {
  vi.mocked(moveAssets).mockResolvedValue({
    moved: 1,
    cancelled: 0,
    conflicted: 1,
    movedAssetIds: [5],
  });
  const {performMove} = mountMoveDrag();

  await performMove(30, [5], []);

  expect(displayNotification).toHaveBeenCalledWith('notice', 'Item moved.');
});

it('confirms a large move first, and doesn’t offer to undo it', async () => {
  vi.mocked(getMoveInfo).mockResolvedValue({count: 51, totalSize: 1});
  const {performMove} = mountMoveDrag();

  confirm.mockReturnValue(false);
  await expect(performMove(30, [5], [])).resolves.toBe(0);
  expect(confirm).toHaveBeenCalledWith(
    'Are you sure you want to move the selected items?'
  );
  expect(moveAssets).not.toHaveBeenCalled();

  confirm.mockReturnValue(true);
  await performMove(30, [5], []);
  expect(moveAssets).toHaveBeenCalledOnce();
  expect(displayNotification).toHaveBeenCalledWith('notice', 'Item moved.');
});

it('shows why a move failed when the server says', async () => {
  vi.mocked(moveFolders).mockRejectedValue({
    isHttpError: true,
    response: {data: {message: 'A folder can’t be moved into itself.'}},
  });
  const {performMove} = mountMoveDrag();

  await expect(performMove(30, [], [9])).rejects.toBeTruthy();

  expect(Craft.cp?.displayError).toHaveBeenCalledWith(
    'A folder can’t be moved into itself.'
  );
});
