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
  it('opens it when its row is clicked', () => {
    const index = mountIndex();

    const handled = index.itemBehavior.onClick?.(
      folderRow(index),
      click(document.createElement('td'))
    );

    expect(handled).toBe(true);
    expect(lastQuery()).toMatchObject({folderId: 7});
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

  it.each(['Enter', ' '])('opens it with %j', (key) => {
    const index = mountIndex();
    const event = new KeyboardEvent('keydown', {key, cancelable: true});

    const handled = index.itemBehavior.onKeydown?.(folderRow(index), event);

    expect(handled).toBe(true);
    expect(event.defaultPrevented).toBe(true);
    expect(lastQuery()).toMatchObject({folderId: 7});
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
