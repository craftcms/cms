import {t} from '@craftcms/ui';
import {useAnnouncer} from '@/common/composables/useAnnouncer';
import type {EditorState} from './useEditorState';

/**
 * Screen-reader announcements for the keyboard editing flow.
 *
 * The legacy editor built these position strings as bare template literals, so
 * they stayed English in every locale. They go through `t()` here.
 */
export function useEditorAnnouncements(state: EditorState) {
  const {announce} = useAnnouncer();

  /** Where an object sits, as a percentage across and down the image. */
  function positionMessage(
    item: {left: number; top: number} | null
  ): string | null {
    const image = state.image.value;

    if (!item || !image || !item.left || !item.top) {
      return null;
    }

    const x = (
      ((item.left - image.left + image.getScaledWidth() / 2) /
        image.getScaledWidth()) *
      100
    ).toFixed(1);
    const y = (
      ((item.top - image.top + image.getScaledHeight() / 2) /
        image.getScaledHeight()) *
      100
    ).toFixed(1);

    return t('Centered at X axis: {x}%, Y axis: {y}%.', {x, y});
  }

  function sizeAndPositionMessage(
    item: {left: number; top: number; width: number; height: number} | null
  ): string | null {
    if (!item) {
      return null;
    }

    const size = t('Crop rectangle width: {width}px, height: {height}px.', {
      width: Math.round(item.width),
      height: Math.round(item.height),
    });

    return [size, positionMessage(item)].filter(Boolean).join(' ');
  }

  function announcePosition(item: {left: number; top: number} | null): void {
    announce(positionMessage(item));
  }

  function announceSizeAndPosition(
    item: {left: number; top: number; width: number; height: number} | null
  ): void {
    announce(sizeAndPositionMessage(item));
  }

  function announcePickUp(
    itemName: string,
    item: {left: number; top: number} | null
  ): void {
    announce(
      [
        t('{item} picked up.', {item: itemName}),
        positionMessage(item),
        t('Use the arrow keys to change position, Tab or Spacebar to drop.'),
      ]
        .filter(Boolean)
        .join(' ')
    );
  }

  function announceDrop(
    itemName: string,
    item: {left: number; top: number} | null
  ): void {
    announce(
      [t('{item} dropped.', {item: itemName}), positionMessage(item)]
        .filter(Boolean)
        .join(' ')
    );
  }

  return {
    announce,
    positionMessage,
    sizeAndPositionMessage,
    announcePosition,
    announceSizeAndPosition,
    announcePickUp,
    announceDrop,
  };
}

export type EditorAnnouncements = ReturnType<typeof useEditorAnnouncements>;
