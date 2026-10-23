import {effectScope, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {ContentIndexData} from '@/modules/elements/index/composables/useContentIndexData';
import {indexData} from '@/modules/elements/index/fixtures/indexData';
import {useModalElementIndex} from './useModalElementIndex';

const post = vi.hoisted(() => vi.fn());

vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@craftcms/ui')>()),
  actionClient: {post},
}));

const volume = {type: 'native', key: 'volume:abc', label: 'Uploads'};
const otherVolume = {type: 'native', key: 'volume:def', label: 'Documents'};

function assetsData(overrides: Partial<ContentIndexData> = {}) {
  return indexData(undefined, {
    elementType: 'Asset',
    source: volume,
    sources: [volume, otherVolume],
    data: [
      {id: 'folder:7', label: 'Photos', isFolder: true, folderId: 7},
      {id: 12, label: 'cat.jpg'},
    ],
    ...overrides,
  } as Partial<ContentIndexData>);
}

let scope: ReturnType<typeof effectScope>;

function mountIndex(initial = assetsData()) {
  scope = effectScope();

  return scope.run(() =>
    useModalElementIndex({
      action: 'element-selector-modals/body',
      initial,
      params: {elementType: 'Asset'},
    })
  )!;
}

/** The query of the latest index request. */
function lastQuery(): Record<string, unknown> {
  return post.mock.lastCall?.[1] as Record<string, unknown>;
}

function folderRow(index: ReturnType<typeof mountIndex>) {
  return index.view.elementIndex.data[0]!;
}

function click(target: Element, init: MouseEventInit = {}) {
  const event = new MouseEvent('click', {
    bubbles: true,
    cancelable: true,
    ...init,
  });
  Object.defineProperty(event, 'target', {value: target});

  return event;
}

/** Double-clicks `target` inside `item`, returning what `onDblClick` said. */
function dblClick(
  index: ReturnType<typeof mountIndex>,
  row: (typeof index.view.elementIndex.data)[number],
  target?: HTMLElement
) {
  const item = document.createElement('li');
  item.append(target ?? document.createElement('span'));
  let handled: boolean | undefined;
  let prevented = false;
  item.addEventListener(
    'dblclick',
    (event) => {
      handled = index.itemBehavior.onDblClick?.(row, event as MouseEvent);
      prevented = event.defaultPrevented;
    },
    {once: true}
  );
  item.firstElementChild!.dispatchEvent(
    new MouseEvent('dblclick', {bubbles: true, cancelable: true})
  );

  return {handled, prevented};
}

beforeEach(() => {
  vi.stubGlobal('Craft', {systemUid: 'test', pageTrigger: 'p'});
  post.mockResolvedValue({data: {props: assetsData()}});
});

afterEach(() => {
  scope?.stop();
  post.mockReset();
  vi.unstubAllGlobals();
});

it('marks folder rows, so a double-click on one isn’t taken as a choice', () => {
  const index = mountIndex();

  expect(index.itemBehavior.attrs?.(folderRow(index))).toEqual({
    'data-is-folder': '',
  });
  expect(
    index.itemBehavior.attrs?.(index.view.elementIndex.data[1]!)
  ).toBeUndefined();
});

describe('opening a folder', () => {
  // Only its title link opens it. Folders can't be selected either, so a click
  // anywhere else on the row does nothing.
  it('ignores a click elsewhere on its row', () => {
    const index = mountIndex();

    const handled = index.itemBehavior.onClick?.(
      folderRow(index),
      click(document.createElement('td'))
    );

    expect(handled).toBe(true);
    expect(post).not.toHaveBeenCalled();
  });

  it('opens it in place when its title link is clicked', () => {
    const index = mountIndex();
    const link = document.createElement('a');
    link.setAttribute('data-folder-link', '');
    const event = click(link);

    index.itemBehavior.onClick?.(folderRow(index), event);

    expect(event.defaultPrevented).toBe(true);
    expect(lastQuery()).toMatchObject({folderId: 7});
  });

  it('leaves a modified click on its title link to the browser', () => {
    const index = mountIndex();
    const link = document.createElement('a');
    link.setAttribute('data-folder-link', '');
    const event = click(link, {metaKey: true});

    const handled = index.itemBehavior.onClick?.(folderRow(index), event);

    expect(handled).toBe(true);
    expect(event.defaultPrevented).toBe(false);
    expect(post).not.toHaveBeenCalled();
  });

  it('opens it with Enter', () => {
    const index = mountIndex();
    const event = new KeyboardEvent('keydown', {
      key: 'Enter',
      cancelable: true,
    });

    const handled = index.itemBehavior.onKeydown?.(folderRow(index), event);

    expect(handled).toBe(true);
    expect(event.defaultPrevented).toBe(true);
    expect(lastQuery()).toMatchObject({folderId: 7});
  });

  it('neither opens nor selects it with Space', () => {
    const index = mountIndex();
    const event = new KeyboardEvent('keydown', {key: ' ', cancelable: true});

    const handled = index.itemBehavior.onKeydown?.(folderRow(index), event);

    expect(handled).toBe(true);
    expect(post).not.toHaveBeenCalled();
  });

  it('clears the selection made in the listing it leaves', () => {
    const index = mountIndex();
    index.view.table.getRowModel().rows[1]!.toggleSelected(true);

    index.openFolder(7);

    expect(index.view.selection.selectedIds.value).toEqual([]);
  });

  it('leaves clicks on assets to the selection', () => {
    const index = mountIndex();

    expect(
      index.itemBehavior.onClick?.(
        index.view.elementIndex.data[1]!,
        click(document.createElement('td'))
      )
    ).toBe(false);
    expect(post).not.toHaveBeenCalled();
  });
});

describe('double-clicking', () => {
  it('selects an asset and lets the double-click through, to choose it', () => {
    const index = mountIndex();

    expect(dblClick(index, index.view.elementIndex.data[1]!)).toEqual({
      handled: false,
      prevented: false,
    });
    expect(index.view.selection.selectedIds.value).toEqual([12]);
  });

  it('keeps an asset its clicks already selected', () => {
    const index = mountIndex();
    index.view.table.getRowModel().rows[1]!.toggleSelected(true);

    dblClick(index, index.view.elementIndex.data[1]!);

    expect(index.view.selection.selectedIds.value).toEqual([12]);
  });

  it('opens a folder instead of choosing it', () => {
    const index = mountIndex();

    expect(dblClick(index, folderRow(index))).toEqual({
      handled: true,
      prevented: true,
    });
    expect(lastQuery()).toMatchObject({folderId: 7});
  });

  it('chooses nothing from a disabled asset', () => {
    scope = effectScope();
    const index = scope.run(() =>
      useModalElementIndex({
        action: 'element-selector-modals/body',
        initial: assetsData(),
        params: {elementType: 'Asset'},
        disabledElementIds: () => [12],
      })
    )!;

    expect(dblClick(index, index.view.elementIndex.data[1]!).handled).toBe(
      true
    );
    expect(index.view.selection.selectedIds.value).toEqual([]);
  });

  it('chooses nothing from a control inside an asset', () => {
    const index = mountIndex();

    expect(
      dblClick(
        index,
        index.view.elementIndex.data[1]!,
        document.createElement('button')
      ).handled
    ).toBe(true);
    expect(index.view.selection.selectedIds.value).toEqual([]);
  });
});

describe('switching sources', () => {
  it('pages the new source by its own total', async () => {
    const pagination = (total: number) => ({
      total,
      per_page: 50,
      current_page: 1,
      last_page: Math.ceil(total / 50),
      next_page_url: null,
      prev_page_url: null,
      from: 1,
      to: Math.min(total, 50),
    });
    const index = mountIndex(
      assetsData({pagination: pagination(58)} as Partial<ContentIndexData>)
    );
    expect(index.view.table.getPageCount()).toBe(2);

    post.mockResolvedValue({
      data: {
        props: assetsData({
          source: otherVolume,
          pagination: pagination(6),
        } as Partial<ContentIndexData>),
      },
    });
    index.changeSource(otherVolume.key);
    await vi.waitFor(() =>
      expect(index.view.elementIndex.source?.key).toBe(otherVolume.key)
    );

    expect(index.view.table.getPageCount()).toBe(1);
  });

  it('marks the chosen source active before its listing loads', async () => {
    const index = mountIndex();
    post.mockReturnValue(new Promise(() => {}));

    index.changeSource(otherVolume.key);

    expect(index.activeSourceKey.value).toBe(otherVolume.key);
    expect(index.view.elementIndex.source?.key).toBe(volume.key);
  });
});

describe('leaving a folder', () => {
  async function openPhotos(index: ReturnType<typeof mountIndex>) {
    index.openFolder(7);
    await nextTick();
    await Promise.resolve();
  }

  it('drops the folder when switching to another volume', async () => {
    const index = mountIndex();
    await openPhotos(index);

    index.changeSource(otherVolume.key);

    expect(lastQuery()).toMatchObject({source: otherVolume.key});
    expect(lastQuery()).not.toHaveProperty('folderId');
  });

  it('goes back to the volume’s root when its source is chosen again', async () => {
    const index = mountIndex();
    await openPhotos(index);
    post.mockClear();

    index.changeSource(volume.key);

    expect(lastQuery()).toMatchObject({source: volume.key});
    expect(lastQuery()).not.toHaveProperty('folderId');
  });
});

describe('picking folders', () => {
  function mountFolderPicker(disabledFolderIds: number[] = []) {
    scope = effectScope();

    return scope.run(() =>
      useModalElementIndex({
        action: 'element-selector-modals/body',
        initial: assetsData({
          data: [
            {id: 'folder:7', label: 'Photos', isFolder: true, folderId: 7},
            {id: 'folder:8', label: 'Video', isFolder: true, folderId: 8},
          ],
        } as Partial<ContentIndexData>),
        params: {elementType: 'Asset'},
        selectFolders: true,
        disabledFolderIds: () => disabledFolderIds,
      })
    )!;
  }

  it('makes folders selectable, bar the disabled ones, and hands back their folder ids', async () => {
    const index = mountFolderPicker([8]);
    const [photos, video] = index.view.table.getRowModel().rows;

    expect(photos!.getCanSelect()).toBe(true);
    expect(video!.getCanSelect()).toBe(false);

    photos!.toggleSelected(true);
    await nextTick();

    expect(index.selectedElements.value).toEqual([
      expect.objectContaining({id: 7, folderId: 7}),
    ]);
  });

  it('selects one folder at a time', async () => {
    const index = mountFolderPicker();
    const [photos, video] = index.view.table.getRowModel().rows;

    photos!.toggleSelected(true);
    video!.toggleSelected(true);
    await nextTick();

    expect(index.selectedElements.value.map(({folderId}) => folderId)).toEqual([
      8,
    ]);
  });

  it('leaves a click elsewhere on a folder row to the selection', () => {
    const index = mountFolderPicker();
    const row = document.createElement('div');

    expect(index.itemBehavior.onClick?.(folderRow(index), click(row))).toBe(
      false
    );
  });

  it('still opens a folder from its title link', () => {
    const index = mountFolderPicker();
    const link = document.createElement('a');
    link.setAttribute('data-folder-link', '');

    expect(index.itemBehavior.onClick?.(folderRow(index), click(link))).toBe(
      true
    );
    expect(lastQuery()).toMatchObject({folderId: 7});
  });
});
