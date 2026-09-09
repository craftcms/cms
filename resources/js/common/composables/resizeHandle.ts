/**
 * What a resize handle needs from whatever it drives.
 *
 * The CP has two shapes of resizing — {@link useResizable} for a layout column
 * and {@link useResizableBox} for a centered box — and they differ in almost
 * everything: one axis against stored bounds versus two axes against a CSS cap.
 * What they share is how a handle component talks to them, so that much is
 * named here rather than restated in each return type.
 */
export interface ResizeHandleControls {
  /** Template ref callback for the handle element. Wire to `:ref`. */
  setHandle: (el: HTMLElement | null) => void;
  /** Resize keys — arrows to nudge, Enter to reset. Wire to `@keydown`. */
  onKeydown: (ev: KeyboardEvent) => void;
  /** Back to the default size. Wire to `@dblclick`. */
  reset: () => void;
}
