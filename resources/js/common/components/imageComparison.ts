/** An image to compare, with the details to compare it by. */
export interface ComparedImage {
  /** Unique among the images compared with the first one. */
  key: string | number;
  /** Names the image: its menu item, its link, and its alternative text. */
  title: string;
  /** Its preview, or `null` when there's none to show. */
  src: string | null;
  /** A page about it, which its column's header links to, in a new window. */
  url?: string | null;
  /** Its details' values, by key. One that's missing or `null` is left blank, and isn't compared. */
  details: Record<string, string | null | undefined>;
}

/** A detail to compare images by, in its own row. */
export interface ComparedDetail {
  key: string;
  label: string;
  /**
   * Whether the row is marked when the two values differ. Off for details
   * that tell copies apart without making them different, such as when
   * each was uploaded.
   */
  compared?: boolean;
}

export type ImageComparisonView = 'sideBySide' | 'slider';
