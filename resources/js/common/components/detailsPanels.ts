/**
 * A panel of a screen’s details column, and its trigger in the rail. The
 * `ScreenPanel` that added it renders the panel itself.
 */
export interface DetailsPanel {
  id: string;
  label: string;
  /** The heading's id, which names the panel and takes focus when it opens. */
  headingId: string;
  icon: string;
  /** Show the label's first letter instead of the icon. */
  initialIcon?: boolean;
  order?: number;
  /** A status shown in the panel header. */
  statusData?: DetailsPanelStatus | null;
  /**
   * Whether the panel belongs to the shell rather than a page, so it stays
   * from page to page. A page’s details column opens on the page’s own first
   * panel rather than one of these.
   */
  persistent?: boolean;
  /** Whether the panel scrolls its own content, so its body doesn't scroll or keep room for a scrollbar. */
  scrollsItself?: boolean;
  /** What gets focus when the panel is opened from its trigger. Defaults to its heading. */
  initialFocus?: () => HTMLElement | null | undefined;
}

export interface DetailsPanelStatus {
  label: string;
  indicator: string;
}
