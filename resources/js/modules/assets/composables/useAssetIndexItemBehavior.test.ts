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
    itemBehavior.onKeydown?.(
      folder,
      new KeyboardEvent('keydown', {key: 'Enter', cancelable: true})
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

it('opens a folder from its title link, unless the click is modified', () => {
  const {itemBehavior} = useAssetIndexItemBehavior({visit});
  const folder = {
    id: 'folder:7',
    isFolder: true,
    folderUrl: '/cp/assets/photos/products',
  };
  const link = document.createElement('a');
  link.href = folder.folderUrl;
  link.dataset.folderLink = '';
  const label = link.appendChild(document.createElement('span'));
  document.body.append(link);

  const click = (init: MouseEventInit = {}) => {
    const event = new MouseEvent('click', {
      bubbles: true,
      cancelable: true,
      ...init,
    });
    let handled: boolean | undefined;
    const onClick = (e: MouseEvent) => {
      handled = itemBehavior.onClick?.(folder, e);
    };
    document.addEventListener('click', onClick, {once: true});
    label.dispatchEvent(event);
    return {event, handled};
  };

  const modified = click({metaKey: true});
  expect(modified.handled).toBe(true);
  expect(modified.event.defaultPrevented).toBe(false);
  expect(visit).not.toHaveBeenCalled();

  const plain = click();
  expect(plain.handled).toBe(true);
  expect(plain.event.defaultPrevented).toBe(true);
  expect(visitedUrl().pathname).toBe('/cp/assets/photos/products');

  link.remove();
});

// A folder only opens from its link, or Enter. Anywhere else, a click or Space
// selects it, as it would an asset — in a table row, a card or a thumb alike.
it.each([
  ['table row', () => document.createElement('tr')],
  ['card', () => document.createElement('craft-card')],
  ['thumb', () => document.createElement('li')],
])(
  'leaves a click or Space on a folder %s, outside its link, to selection',
  (_, createItem) => {
    const {itemBehavior} = useAssetIndexItemBehavior({visit});
    const folder = {
      id: 'folder:7',
      isFolder: true,
      folderUrl: '/cp/assets/photos/products',
    };
    const item = createItem();
    const body = item.appendChild(document.createElement('span'));
    const click = new MouseEvent('click', {bubbles: true, cancelable: true});
    Object.defineProperty(click, 'target', {value: body});
    const space = new KeyboardEvent('keydown', {key: ' ', cancelable: true});
    Object.defineProperty(space, 'target', {value: item});

    expect(itemBehavior.onClick?.(folder, click)).toBe(false);
    expect(itemBehavior.onKeydown?.(folder, space)).toBe(false);
    expect(visit).not.toHaveBeenCalled();

    const enter = new KeyboardEvent('keydown', {
      key: 'Enter',
      cancelable: true,
    });
    Object.defineProperty(enter, 'target', {value: item});

    expect(itemBehavior.onKeydown?.(folder, enter)).toBe(true);
    expect(visit).toHaveBeenCalledTimes(1);
  }
);

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

/** Double-clicks `target` inside `row`, handing the event to `onDblClick`. */
function dblClick(
  row: HTMLElement,
  target: HTMLElement,
  handle: (event: MouseEvent) => boolean | undefined
): boolean | undefined {
  let result: boolean | undefined;
  row.addEventListener(
    'dblclick',
    (event) => {
      result = handle(event as MouseEvent);
    },
    {once: true}
  );
  target.dispatchEvent(
    new MouseEvent('dblclick', {bubbles: true, cancelable: true})
  );

  return result;
}

function folderRow(): {
  row: HTMLElement;
  body: HTMLElement;
  button: HTMLElement;
} {
  const row = document.createElement('div');
  const body = document.createElement('span');
  const button = document.createElement('button');
  row.append(body, button);
  document.body.append(row);

  return {row, body, button};
}

const folder = {
  id: 'folder:7',
  isFolder: true,
  folderUrl: '/cp/assets/photos/products',
  folderId: 7,
};

it('opens a folder when its row, card or thumb is double-clicked', () => {
  const {itemBehavior} = useAssetIndexItemBehavior({visit});
  const {row, body} = folderRow();

  expect(
    dblClick(row, body, (event) => itemBehavior.onDblClick?.(folder, event))
  ).toBe(true);
  expect(visitedUrl().pathname).toBe('/cp/assets/photos/products');
});

it('leaves a double-click on a control inside a folder to the control', () => {
  const {itemBehavior} = useAssetIndexItemBehavior({visit});
  const {row, button} = folderRow();

  // Still handled, so the index doesn't try to edit the folder instead.
  expect(
    dblClick(row, button, (event) => itemBehavior.onDblClick?.(folder, event))
  ).toBe(true);
  expect(visit).not.toHaveBeenCalled();
});

it('leaves double-clicked assets to the index, which edits them', () => {
  const {itemBehavior} = useAssetIndexItemBehavior({visit});
  const {row, body} = folderRow();

  expect(
    dblClick(row, body, (event) => itemBehavior.onDblClick?.({id: 42}, event))
  ).toBe(false);
  expect(visit).not.toHaveBeenCalled();
});
