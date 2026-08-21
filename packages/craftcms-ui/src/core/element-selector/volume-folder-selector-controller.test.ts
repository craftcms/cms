import {describe, expect, it, vi} from 'vite-plus/test';
import {
  ASSET_ELEMENT_TYPE,
  VolumeFolderSelectorController,
} from './volume-folder-selector-controller.js';
import type {
  SourcePathSegment,
  VolumeFolderIndexAdapter,
  VolumeFolderSelectorOptions,
} from './volume-folder-selector-controller.js';
import type {ElementInfo} from './types.js';

function create(options: Partial<VolumeFolderSelectorOptions> = {}) {
  return new VolumeFolderSelectorController({
    hideOnSelect: false,
    loadIndexBody: async () => ({html: '', props: {}}),
    ...options,
  });
}

/** Stands in for the legacy jQuery index, which owns the breadcrumb. */
function folderIndex(
  sourcePath: SourcePathSegment[]
): VolumeFolderIndexAdapter {
  return {
    sourcePath,
    clearSelection: vi.fn(),
    destroy: vi.fn(),
  };
}

function folderRow(folderId: number): ElementInfo {
  return {
    id: 900 + folderId,
    folderId,
    siteId: null,
    label: `Folder ${folderId}`,
    status: null,
    url: null,
    hasThumb: false,
  };
}

describe('configuration', () => {
  it('is always the asset element type', () => {
    expect(create().elementType).toBe(ASSET_ELEMENT_TYPE);
  });

  it('browses folders only', () => {
    expect(create().indexParams().foldersOnly).toBe(true);
    expect(create().indexSettings().foldersOnly).toBe(true);
  });

  it('never offers the site menu, since folders do not vary by site', () => {
    expect(create({showSiteMenu: true}).indexParams().showSiteMenu).toBe('0');
  });

  it('passes disabledFolderIds to the index', () => {
    expect(create({disabledFolderIds: [3, 4]}).indexSettings()).toMatchObject({
      disabledFolderIds: [3, 4],
    });
  });
});

describe('selecting a highlighted folder', () => {
  it('hands back the row’s folderId', async () => {
    const onSelect = vi.fn();
    const controller = create({onSelect});
    controller.setSelection([folderRow(12)]);

    await controller.submit();

    expect(onSelect.mock.calls[0]![0]).toHaveLength(1);
    expect(onSelect.mock.calls[0]![0][0].folderId).toBe(12);
  });
});

describe('selecting the open folder', () => {
  it('can submit with nothing highlighted', () => {
    // Unreachable in the legacy modal: it gated on
    // `ev.currentTarget === this.$selectBtn[0]`, but the base bound the click as
    // a zero-argument arrow, so `ev` was always undefined and Select did nothing.
    const controller = create();
    controller.attachIndex(folderIndex([{folderId: 1}, {folderId: 7}]));

    expect(controller.hasSelection).toBe(false);
    expect(controller.state.canSubmit).toBe(true);
  });

  it('hands back the deepest breadcrumb folder', async () => {
    const onSelect = vi.fn();
    const controller = create({onSelect});
    controller.attachIndex(
      folderIndex([
        {folderId: 1, label: 'Volume'},
        {folderId: 7, label: 'Nested'},
      ])
    );

    await controller.submit();

    expect(onSelect.mock.calls[0]![0][0]).toMatchObject({
      folderId: 7,
      label: 'Nested',
    });
  });

  it('cannot submit with no index attached', () => {
    expect(create().state.canSubmit).toBe(false);
  });

  it('cannot submit with an empty breadcrumb', () => {
    const controller = create();
    controller.attachIndex(folderIndex([]));

    expect(controller.state.canSubmit).toBe(false);
  });

  it('cannot submit when the breadcrumb has no folder id', () => {
    const controller = create();
    controller.attachIndex(folderIndex([{label: 'All volumes'}]));

    expect(controller.state.canSubmit).toBe(false);
  });

  it('refuses a folder in the disabled set', () => {
    // Moving a folder into itself.
    const controller = create({disabledFolderIds: [7]});
    controller.attachIndex(folderIndex([{folderId: 7}]));

    expect(controller.currentFolderId()).toBeNull();
    expect(controller.state.canSubmit).toBe(false);
  });

  it('still allows a highlighted row when the open folder is disabled', () => {
    const controller = create({disabledFolderIds: [7]});
    controller.attachIndex(folderIndex([{folderId: 7}]));
    controller.setSelection([folderRow(12)]);

    expect(controller.state.canSubmit).toBe(true);
  });
});
