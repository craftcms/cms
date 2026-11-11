/**
 * The element data a chip draws, as `ElementHtml::presentation()` gives it for
 * a list view. Everything past `id` and `label` is optional.
 */
export interface ChipElement {
  id: number;
  label: string;
  siteId?: number | string | null;
  /**
   * Already resolved to a fill and a label. Which statuses exist, and what
   * they're called, is an element-type concern the server settles.
   */
  status?: {fill: string; label: string; draft: boolean} | null;
  thumbHtml?: string | null;
  /** A Craft color name, such as an entry's type color. */
  color?: string | null;
  /** Where the element is edited, which a `linked` chip's label goes to. */
  cpEditUrl?: string | null;
  /**
   * The element's structure level. Elements deeper than the one before them
   * are nested under it, as a relation field that maintains hierarchy shows
   * them.
   */
  level?: number | null;
}

/**
 * The element data `ElementList` draws, as `ElementHtml::presentation()` gives
 * it for the list's view mode. Card views use the `card*` parts, and the
 * server only renders the parts the mode uses.
 */
export interface ListElement extends ChipElement {
  url?: string | null;
  cardAttributes?: Record<string, string | number | boolean | null | undefined>;
  cardHeaderHtml?: string;
  cardActionsHtml?: string;
  cardThumbHtml?: string;
  thumbAlignment?: string;
  cardContentHtml?: string;
  cardFooterHtml?: string;
}
