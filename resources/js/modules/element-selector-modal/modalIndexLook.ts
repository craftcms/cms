import {prefixedKey} from '@/common/composables/useStorage';
import type {
  CardThumbSettings,
  ThumbSize,
  ViewMode,
} from '@/modules/elements/types/view-state';

/**
 * How an element type's selector modal last drew its index, so the next one
 * can draw placeholders the same way before its first payload arrives.
 *
 * Kept apart from the persisted view state, because that names the mode the
 * user picked, which the index may not offer and so may not have shown.
 */
export interface ModalIndexLook {
  mode: ViewMode['mode'];
  thumbSize: ThumbSize;
  cardThumbs: CardThumbSettings | null;
}

function key(elementType: string): string {
  return prefixedKey(`elementindex.${elementType}.modal.look`);
}

export function readModalIndexLook(elementType: string): ModalIndexLook | null {
  try {
    return JSON.parse(localStorage.getItem(key(elementType)) ?? 'null');
  } catch {
    return null;
  }
}

export function writeModalIndexLook(
  elementType: string,
  look: ModalIndexLook
): void {
  localStorage.setItem(key(elementType), JSON.stringify(look));
}
