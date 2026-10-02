import {actionClient} from '@src/utilities/api/actionClient.js';
import {ElementSelectorController} from './element-selector-controller.js';
import type {
  ElementIndexAdapter,
  ElementInfo,
  ElementSelectorOptions,
  SelectMeta,
} from './types.js';

export interface AssetTransform {
  handle: string;
  name: string;
}

/** Resolves an asset's URL under a transform, or `false` if it has none. */
export type FetchTransformUrl = (
  assetId: number,
  handle: string
) => Promise<string | false>;

export interface AssetSelectorOptions extends ElementSelectorOptions {
  /** Transforms offered before selecting. Empty means no transform menu. */
  transforms?: AssetTransform[];
  /** Test seam; defaults to a POST to `assets/generate-transform`. */
  fetchTransformUrl?: FetchTransformUrl;
}

const defaultFetchTransformUrl: FetchTransformUrl = async (assetId, handle) => {
  const {data} = await actionClient.post('assets/generate-transform', {
    assetId,
    handle,
  });

  return (data?.url as string | undefined) || false;
};

/**
 * Adds image transforms to the element selector.
 *
 * The user may pick a transform instead of plain Select; the chosen transform's
 * URL is resolved for every selected asset and spliced into the payload.
 *
 * @example
 * const controller = new AssetSelectorController({
 *   elementType: 'CraftCms\\Cms\\Asset\\Elements\\Asset',
 *   transforms: [{handle: 'thumb', name: 'Thumbnail'}],
 *   onSelect: (elements, {transform}) => …,
 * });
 *
 * await controller.selectWithTransform('thumb');
 */
export class AssetSelectorController<
  A extends ElementIndexAdapter = ElementIndexAdapter,
> extends ElementSelectorController<A> {
  /**
   * Cross-instance cache of resolved URLs, keyed `[handle][assetId]`.
   *
   * Static, as it was in the legacy modal: transform URLs are stable for an
   * asset, and modals are constructed per relation field, so sharing the cache
   * saves a request per field per asset.
   */
  static transformUrls: Record<string, Record<number, string | false>> = {};

  #selectedTransform: string | null = null;

  declare readonly options: ElementSelectorController<A>['options'] &
    AssetSelectorOptions;

  constructor(options: AssetSelectorOptions) {
    super(options);
  }

  get transforms(): readonly AssetTransform[] {
    return this.options.transforms ?? [];
  }

  /** Whether the transform menu should be available right now. */
  get canApplyTransform(): boolean {
    return this.selection.length > 0 && this.transforms.length > 0;
  }

  /**
   * Resolve the transform's URL for every selected asset, then submit.
   *
   * Holds `busy` across the fetches, so the chrome shows a spinner and can't be
   * clicked again mid-flight.
   */
  async selectWithTransform(handle: string): Promise<void> {
    const cache = (AssetSelectorController.transformUrls[handle] ??= {});
    const missing = this.selection
      .map((element) => Number(element.id))
      .filter((id) => cache[id] === undefined);

    if (missing.length > 0) {
      const fetchUrl =
        this.options.fetchTransformUrl ?? defaultFetchTransformUrl;

      this.setBusy(true);

      try {
        // Sequential, matching the legacy modal: transform generation is
        // expensive server-side and firing a burst of them was deliberate to
        // avoid.
        for (const id of missing) {
          try {
            cache[id] = await fetchUrl(id, handle);
          } catch {
            // A failed transform falls back to the untransformed URL rather
            // than blocking the selection.
            cache[id] = false;
          }
        }
      } finally {
        this.setBusy(false);
      }
    }

    this.#selectedTransform = handle;

    try {
      await this.submit();
    } finally {
      this.#selectedTransform = null;
    }
  }

  protected override buildElementInfo(
    selection: readonly ElementInfo[]
  ): ElementInfo[] {
    const info = super.buildElementInfo(selection);

    if (!this.#selectedTransform) {
      return info;
    }

    const cache =
      AssetSelectorController.transformUrls[this.#selectedTransform] ?? {};

    for (const item of info) {
      const url = cache[Number(item.id)];

      if (url !== undefined && url !== false) {
        item.url = url;
      }
    }

    return info;
  }

  protected override selectMeta(): SelectMeta {
    return {transform: this.#selectedTransform};
  }
}
