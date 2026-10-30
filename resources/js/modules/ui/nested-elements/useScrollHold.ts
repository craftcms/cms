import {nextTick, watch, type Ref, type WatchSource} from 'vue';

/**
 * Holds the page still while the field is rebuilt.
 *
 * The document loses the field's height while it's gone, and the browser
 * answers by scrolling to the top — which is what deleting a block used to
 * look like. Take down where everything was on the way in, and put it back
 * once the new field is in place.
 */
export function useScrollHold(
  rebuild: WatchSource<unknown>,
  host: Readonly<Ref<HTMLElement | undefined>>
) {
  /**
   * Set when the render that's coming is one we'll scroll somewhere specific
   * for anyway, so holding the old position would only fight it.
   */
  let revealing = false;

  watch(rebuild, () => {
    if (revealing) {
      return;
    }

    const anchors = scrollAnchors();

    void nextTick(() => {
      for (const [node, top] of anchors) {
        if (node.scrollTop !== top) {
          node.scrollTop = top;
        }
      }
    });
  });

  /** Everything that scrolls around the field, and how far it's scrolled. */
  function scrollAnchors(): Array<[Element, number]> {
    const anchors: Array<[Element, number]> = [];
    const root = document.scrollingElement;

    if (root) {
      anchors.push([root, root.scrollTop]);
    }

    for (
      let node = host.value?.parentElement;
      node;
      node = node.parentElement
    ) {
      if (node.scrollHeight > node.clientHeight) {
        anchors.push([node, node.scrollTop]);
      }
    }

    return anchors;
  }

  return {
    /** Lets the next rebuild scroll freely, for a block about to be revealed. */
    setRevealing(value: boolean): void {
      revealing = value;
    },
  };
}
