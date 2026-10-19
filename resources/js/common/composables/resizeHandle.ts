/**
 * What a resize handle needs from the {@link useResizable} column it drives.
 *
 * (A centered box, such as a modal, is resized by `<craft-resize-grips>`
 * instead, which handles its own pointer and keyboard input.)
 */
export interface ResizeHandleControls {
  /** Template ref callback for the handle element. Wire to `:ref`. */
  setHandle: (el: HTMLElement | null) => void;
  /** Resize keys — arrows to nudge, Enter to reset. Wire to `@keydown`. */
  onKeydown: (ev: KeyboardEvent) => void;
  /** Back to the default size. Wire to `@dblclick`. */
  reset: () => void;
}
