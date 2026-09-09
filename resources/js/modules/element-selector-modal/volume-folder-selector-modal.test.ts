import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import $ from 'jquery';
import {VolumeFolderSelectorModal} from './volume-folder-selector-modal';

if (typeof HTMLDialogElement !== 'undefined') {
  HTMLDialogElement.prototype.showModal ??= function (this: HTMLDialogElement) {
    this.setAttribute('open', '');
  };
  HTMLDialogElement.prototype.show ??= function (this: HTMLDialogElement) {
    this.setAttribute('open', '');
  };
  HTMLDialogElement.prototype.close ??= function (this: HTMLDialogElement) {
    this.removeAttribute('open');
  };
}

const INDEX_HTML = `
  <div class="element-index">
    <div class="sidebar"><a class="sel">Volume A</a></div>
    <div class="main"><div class="content"><table><tbody></tbody></table></div></div>
  </div>
`;

/** Stands in for the legacy jQuery index the binder boots. */
function fakeIndex(sourcePath: {folderId?: number; label?: string}[] = []) {
  const selected: {folderId: number; label: string}[] = [];

  return {
    sourcePath,
    selected,
    $main: $('<div class="main"></div>'),
    $sidebar: $('<div class="sidebar"><a class="sel">Volume A</a></div>'),
    $elements: $('<div></div>'),
    clearSelection: vi.fn(),
    handleResize: vi.fn(),
    destroy: vi.fn(),
    getSelectedElements() {
      // The legacy index hands back a jQuery collection of row wrappers, each
      // containing an `.element` carrying the folder id.
      const rows = selected.map(
        (f) =>
          $(
            `<div><div class="element" data-folder-id="${f.folderId}" data-label="${f.label}"></div></div>`
          )[0]
      );
      return $(rows);
    },
  };
}

let index: ReturnType<typeof fakeIndex>;
let createElementIndex: ReturnType<typeof vi.fn>;

function createModal(settings: Record<string, unknown> = {}) {
  return new VolumeFolderSelectorModal({
    loadIndexBody: async () => ({html: INDEX_HTML, props: {}}),
    ...settings,
  } as any);
}

/** The binder boots the index from a `change` event, so give it a few ticks. */
async function settle(): Promise<void> {
  for (let i = 0; i < 10; i++) {
    await Promise.resolve();
    await new Promise((r) => setTimeout(r, 0));
  }
}

beforeEach(() => {
  index = fakeIndex();
  createElementIndex = vi.fn(() => index);
  vi.stubGlobal('$', $);
  vi.stubGlobal('Craft', {
    t: (_category: string, message: string) => message,
    createElementIndex,
  });
});

afterEach(() => {
  vi.unstubAllGlobals();
  document.body.replaceChildren();
});

describe('chrome', () => {
  it('opens out of the top layer', async () => {
    // `showModal()` would paint above the `<body>`-appended menus this index
    // depends on.
    const modal = createModal();
    await settle();

    expect(document.body.contains(modal.element)).toBe(true);
    expect(modal.element.nonModal).toBe(true);
    modal.destroy();
  });

  it('takes its copy from the settings', async () => {
    const modal = createModal({
      showTitle: true,
      modalTitle: 'Move to',
      selectBtnLabel: 'Move',
    });
    await settle();

    const shadow = modal.element.shadowRoot!;
    expect(shadow.querySelector('[part="title"]')!.textContent!.trim()).toBe(
      'Move to'
    );
    expect(shadow.querySelector('[part="select"]')!.textContent!.trim()).toBe(
      'Move'
    );
    modal.destroy();
  });
});

describe('booting the legacy index', () => {
  it('slots the server HTML into light DOM so the CP stylesheet reaches it', async () => {
    const modal = createModal();
    await settle();

    expect(modal.element.querySelector('.element-index')).not.toBeNull();
    modal.destroy();
  });

  it('boots the index once, with folders-only settings', async () => {
    const modal = createModal({disabledFolderIds: [7]});
    await settle();
    await modal.controller.reload();
    await settle();

    expect(createElementIndex).toHaveBeenCalledTimes(1);
    const [elementType, , settings] = createElementIndex.mock.calls[0]!;
    expect(elementType).toBe('CraftCms\\Cms\\Asset\\Elements\\Asset');
    expect(settings).toMatchObject({
      foldersOnly: true,
      disabledFolderIds: [7],
      context: 'modal',
    });
    modal.destroy();
  });

  it('asks the server for folders only', async () => {
    const modal = createModal();
    await settle();

    expect(modal.controller.indexParams()).toMatchObject({
      foldersOnly: true,
      // Folders don't vary by site.
      showSiteMenu: '0',
    });
    modal.destroy();
  });
});

describe('selection', () => {
  it('pushes highlighted folders into the controller', async () => {
    const modal = createModal();
    await settle();

    index.selected.push({folderId: 12, label: 'Nested'});
    const settings = createElementIndex.mock.calls[0]![2] as any;
    settings.onSelectionChange();

    expect(modal.controller.state.selection).toHaveLength(1);
    expect(modal.controller.state.selection[0]!.folderId).toBe(12);
    expect(modal.controller.state.canSubmit).toBe(true);
    modal.destroy();
  });

  it('hands the highlighted folder to onSelect', async () => {
    const onSelect = vi.fn();
    const modal = createModal({onSelect, hideOnSelect: false});
    await settle();

    index.selected.push({folderId: 12, label: 'Nested'});
    (createElementIndex.mock.calls[0]![2] as any).onSelectionChange();
    await modal.controller.submit();

    expect(onSelect.mock.calls[0]![0][0]).toMatchObject({folderId: 12});
    modal.destroy();
  });

  it('reads the breadcrumb live, so the open folder is selectable', async () => {
    // This is the rule that never worked in the legacy modal: it gated on an
    // event target that was always undefined.
    const onSelect = vi.fn();
    const modal = createModal({onSelect, hideOnSelect: false});
    await settle();

    expect(modal.controller.state.canSubmit).toBe(false);

    index.sourcePath.push({folderId: 1, label: 'Volume'});
    index.sourcePath.push({folderId: 9, label: 'Open folder'});
    // Navigating re-runs the rules; that is what `onSourcePathChange` is for.
    (createElementIndex.mock.calls[0]![2] as any).onSourcePathChange();

    expect(modal.controller.state.canSubmit).toBe(true);
    await modal.controller.submit();

    expect(onSelect.mock.calls[0]![0][0]).toMatchObject({
      folderId: 9,
      label: 'Open folder',
    });
    modal.destroy();
  });

  it('refuses an open folder that is disabled', async () => {
    const modal = createModal({disabledFolderIds: [9]});
    await settle();
    index.sourcePath.push({folderId: 9});
    (createElementIndex.mock.calls[0]![2] as any).onSourcePathChange();

    expect(modal.controller.state.canSubmit).toBe(false);
    modal.destroy();
  });
});

describe('teardown', () => {
  it('destroys the index and removes the element', async () => {
    const modal = createModal();
    await settle();

    modal.destroy();

    expect(index.destroy).toHaveBeenCalled();
    expect(document.body.contains(modal.element)).toBe(false);
  });
});
