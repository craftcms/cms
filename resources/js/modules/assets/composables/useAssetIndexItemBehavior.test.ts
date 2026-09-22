import {beforeEach, expect, it, vi} from 'vite-plus/test';
import {useAssetIndexItemBehavior} from './useAssetIndexItemBehavior';

const visit = vi.fn();
const hidePreview = vi.fn();
const PreviewFileModal = vi.fn();

function visitedUrl(): URL {
  const href = visit.mock.calls[0]?.[0];
  if (href?.constructor !== String) {
    throw new Error('Expected a folder visit URL.');
  }

  return new URL(String(href), 'http://localhost');
}

function keydown(shiftKey = true): KeyboardEvent {
  return new KeyboardEvent('keydown', {
    key: ' ',
    shiftKey,
    bubbles: true,
    cancelable: true,
  });
}

beforeEach(() => {
  visit.mockReset();
  hidePreview.mockReset();
  PreviewFileModal.mockReset();
  window.history.replaceState({}, '', '/cp/assets/photos?viewMode=cards');
  window.Craft = Object.assign(Object.create(null), {
    pageTrigger: 'page',
    PreviewFileModal: Object.assign(PreviewFileModal, {openInstance: null}),
  });
});

it('opens a folder with the current view and fresh pagination', () => {
  window.history.replaceState(
    {},
    '',
    '/cp/assets/photos?source=volume:abc&page=3&viewMode=cards'
  );
  const {itemBehavior} = useAssetIndexItemBehavior({visit});
  const folder = {
    id: 'folder:7',
    isFolder: true,
    folderUrl: '/cp/assets/photos/products',
    folderId: 7,
    canMoveTo: true,
  };

  expect(
    itemBehavior.onClick?.(
      folder,
      new MouseEvent('click', {bubbles: true, cancelable: true})
    )
  ).toBe(true);
  expect(visitedUrl().pathname).toBe('/cp/assets/photos/products');
  expect(visitedUrl().searchParams).toEqual(
    new URLSearchParams({viewMode: 'cards'})
  );
  expect(visit).toHaveBeenCalledWith(
    expect.any(String),
    expect.objectContaining({preserveState: true, preserveScroll: true})
  );
});

it('marks assets and folders for drag-and-drop', () => {
  const {itemBehavior} = useAssetIndexItemBehavior({visit});

  expect(itemBehavior.attrs?.({id: 42})).toMatchObject({
    'data-row-id': '42',
    'data-id': '42',
    'data-movable-item': '',
  });
  expect(
    itemBehavior.attrs?.({
      id: 'folder:7',
      isFolder: true,
      folderUrl: '/cp/assets/x',
      folderId: 7,
      canMoveTo: true,
    })
  ).toMatchObject({
    'data-row-id': 'folder:7',
    'data-folder-id': '7',
    'data-is-folder': '',
    'data-movable-item': '',
    'data-can-move-to': '',
  });
});

it('allows normal click and Space behavior for assets', () => {
  const {itemBehavior} = useAssetIndexItemBehavior({visit});
  const asset = {id: 42, previewable: true};

  expect(
    itemBehavior.onClick?.(
      asset,
      new MouseEvent('click', {bubbles: true, cancelable: true})
    )
  ).toBe(false);
  expect(itemBehavior.onKeydown?.(asset, keydown(false))).toBe(false);
});

it('opens a preview for the focused asset on Shift+Space', () => {
  const {itemBehavior} = useAssetIndexItemBehavior({visit});
  const event = keydown();

  expect(itemBehavior.onKeydown?.({id: 42, previewable: true}, event)).toBe(
    true
  );
  expect(event.defaultPrevented).toBe(true);
  expect(PreviewFileModal).toHaveBeenCalledWith(42);
});

it('closes the open preview on Shift+Space', () => {
  Object.assign(PreviewFileModal, {openInstance: {hide: hidePreview}});
  const {itemBehavior} = useAssetIndexItemBehavior({visit});

  expect(itemBehavior.onKeydown?.({id: 42, previewable: true}, keydown())).toBe(
    true
  );
  expect(hidePreview).toHaveBeenCalledOnce();
  expect(PreviewFileModal).not.toHaveBeenCalled();
});
