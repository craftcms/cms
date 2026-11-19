import type {Width} from '@/common/types';

/** One of the CP's width steps, or `auto` to fit the content. */
export type ModalWidth = keyof typeof Width | 'auto';

export interface ModalProps {
  isActive?: boolean;
  /** Accessible name; ModalForm supplies its visible title. */
  label?: string;
  overlay?: boolean;
  width?: ModalWidth;
  height?: string;
  maxHeight?: string;
  /** Adds grips along its right and bottom edges, and in their corner, for resizing it. */
  resizable?: boolean;
  /**
   * Whether it keeps the tallest height it has reached while open, so content
   * that shrinks doesn't pull the box out from under the reader. Off for a
   * modal whose content gets replaced by a different screen, such as the
   * steps of a wizard.
   */
  holdHeight?: boolean;
  /**
   * Whether Escape and a click on the overlay close it. Off for a modal
   * holding work that closing would throw away — only its own buttons do.
   */
  dismissible?: boolean;
  /**
   * Whether closing it puts focus back where it was before it opened. Off
   * when whatever closed it moves focus somewhere on purpose.
   */
  restoreFocus?: boolean;
  /**
   * Opens without the enter transition, for a modal opened from the
   * keyboard whose first keystrokes have to reach it. Closing still fades.
   */
  instant?: boolean;
  /** Stops it scrolling, for content that fills it and scrolls its own regions. */
  fill?: boolean;
}
