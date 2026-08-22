import {ElementSelectorController} from './element-selector-controller.js';
import type {
  ElementIndexAdapter,
  ElementInfo,
  ElementSelectorOptions,
} from './types.js';

export const ASSET_ELEMENT_TYPE = 'CraftCms\\Cms\\Asset\\Elements\\Asset';

/** One step of the breadcrumb into the folder currently open. */
export interface SourcePathSegment {
  folderId?: number;
  label?: string;
}

/**
 * A folder index also exposes where it has navigated to.
 *
 * That is the whole reason this controller exists: "select the folder I'm
 * looking at" is a selection the index itself has no row for.
 */
export interface VolumeFolderIndexAdapter extends ElementIndexAdapter {
  readonly sourcePath: readonly SourcePathSegment[];
}

export interface VolumeFolderSelectorOptions extends Omit<
  ElementSelectorOptions,
  'elementType'
> {
  /** Folders that may not be chosen — typically the ones being moved. */
  disabledFolderIds?: number[];
}

/**
 * Picks a volume folder rather than an element.
 *
 * Browses asset volumes folders-only, and — unlike every other selector — allows
 * submitting with nothing highlighted, which means "the folder currently open".
 *
 * That fallback was unreachable in the legacy modal: it gated on
 * `ev.currentTarget === this.$selectBtn[0]`, but the base class bound the click
 * as a zero-argument arrow, so `ev` was always `undefined`. The Select button
 * would enable and then do nothing. Here the rule lives in
 * {@link canSubmitSelection} and {@link buildElementInfo}, so the inherited
 * `submit()` handles both cases with no override and no event to inspect.
 */
export class VolumeFolderSelectorController extends ElementSelectorController<VolumeFolderIndexAdapter> {
  readonly disabledFolderIds: number[];

  constructor(options: VolumeFolderSelectorOptions = {}) {
    const {disabledFolderIds = [], ...rest} = options;

    super({
      ...rest,
      elementType: ASSET_ELEMENT_TYPE,
      // Folders don't vary by site, so the menu is never useful here.
      showSiteMenu: false,
    });

    this.disabledFolderIds = [...disabledFolderIds];
  }

  override indexParams(): Record<string, unknown> {
    return {...super.indexParams(), foldersOnly: true};
  }

  override indexSettings(): Record<string, unknown> {
    return {
      ...super.indexSettings(),
      foldersOnly: true,
      disabledFolderIds: [...this.disabledFolderIds],
    };
  }

  /** The open folder, if it may be selected. */
  currentFolderId(): number | null {
    const sourcePath = this.index?.sourcePath;

    if (!sourcePath?.length) {
      return null;
    }

    const last = sourcePath[sourcePath.length - 1]!;

    // Coerced and range-checked rather than trusted: the breadcrumb comes from
    // the index, which builds it from server HTML, so a missing or unparseable
    // `folderId` has to read as "no folder" instead of leaking NaN into the
    // selection payload.
    const folderId = Number(last.folderId);

    if (!Number.isFinite(folderId)) {
      return null;
    }

    return this.disabledFolderIds.includes(folderId) ? null : folderId;
  }

  protected override canSubmitSelection(): boolean {
    return super.canSubmitSelection() || this.currentFolderId() !== null;
  }

  /**
   * Always `{folderId}`-bearing, whether a row was highlighted or the open
   * folder is being chosen.
   *
   * The legacy modal read `data-folder-id` off each row's DOM; the id now
   * arrives as extra row data from `ModalIndexViewModel::extraRowData()`.
   */
  protected override buildElementInfo(
    selection: readonly ElementInfo[]
  ): ElementInfo[] {
    if (selection.length > 0) {
      return selection.map((element) => ({
        ...element,
        folderId: Number(element.folderId ?? element.id),
      }));
    }

    const folderId = this.currentFolderId();

    if (folderId === null) {
      return [];
    }

    // Shaped as a full ElementInfo so consumers reading `id`/`label` still work;
    // `folderId` is the key `AssetIndex` and `MoveAssets` actually read.
    return [
      {
        id: folderId,
        folderId,
        siteId: null,
        label: this.#currentFolderLabel(),
        status: null,
        url: null,
        hasThumb: false,
      },
    ];
  }

  #currentFolderLabel(): string {
    const sourcePath = this.index?.sourcePath;
    const last = sourcePath?.[sourcePath.length - 1];

    return last?.label ?? '';
  }
}
