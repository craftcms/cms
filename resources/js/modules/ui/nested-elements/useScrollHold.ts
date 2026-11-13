import {nextTick, watch, type Ref, type WatchSource} from 'vue';

/** Where focus was in a block, by its place rather than its element. */
interface FocusAnchor {
  uid: string;
  index: number;
  count: number;
  /** Child indexes from the block down to the focused element. */
  path: number[];
  selection: [number, number] | null;
}

/**
 * Holds the page still while the field is rebuilt.
 *
 * The document loses the field's height while it's gone, and the browser
 * answers by scrolling to the top — which is what deleting a block used to
 * look like. Take down where everything was on the way in, and put it back
 * once the new field is in place.
 *
 * Focus goes with the field too, from under the cursor of someone typing in a
 * block. It goes back to the same place in that block, or, when a save has
 * replaced the blocks with as many new ones, in the block at its position.
 * That can take more than one rebuild: the saved blocks' ui can arrive before
 * the blocks themselves are renamed onto it.
 */
export function useScrollHold(
  rebuild: WatchSource<unknown>,
  host: Readonly<Ref<HTMLElement | undefined>>,
  ids: () => readonly string[]
) {
  /**
   * Set when the render that's coming is one we'll scroll somewhere specific
   * for anyway, so holding the old position would only fight it.
   */
  let revealing = false;
  /** Where focus is to go back to, once its block is back. */
  let focus: FocusAnchor | null = null;

  watch(rebuild, () => {
    if (revealing) {
      focus = null;

      return;
    }

    const anchors = scrollAnchors();
    focus = focusAnchor() ?? (focusLost() ? focus : null);

    void nextTick(() => {
      for (const [node, top] of anchors) {
        if (node.scrollTop !== top) {
          node.scrollTop = top;
        }
      }

      if (focus && restoreFocus(focus)) {
        focus = null;
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

  /** This field's own blocks, leaving out those of fields nested in them. */
  function blocks(): HTMLElement[] {
    const field = host.value;

    return field
      ? [...field.querySelectorAll<HTMLElement>('[data-matrix-block]')].filter(
          (block) =>
            block.parentElement?.closest('[data-matrix-field]') === field
        )
      : [];
  }

  function focusLost(): boolean {
    return !document.activeElement || document.activeElement === document.body;
  }

  function focusAnchor(): FocusAnchor | null {
    const active = document.activeElement;
    const all = blocks();
    const block = active ? all.find((block) => block.contains(active)) : null;

    if (!active || !block?.dataset.id) {
      return null;
    }

    const path: number[] = [];

    for (let node: Element = active; node !== block;) {
      const parent: Element | null = node.parentElement;

      if (!parent) {
        return null;
      }

      path.unshift([...parent.children].indexOf(node));
      node = parent;
    }

    return {
      uid: block.dataset.id,
      index: all.indexOf(block),
      count: all.length,
      path,
      selection:
        (active instanceof HTMLInputElement ||
          active instanceof HTMLTextAreaElement) &&
        active.selectionStart !== null &&
        active.selectionEnd !== null
          ? [active.selectionStart, active.selectionEnd]
          : null,
    };
  }

  /**
   * Puts focus back, and reports whether there's nothing left to do: it's
   * back, or has gone somewhere else meanwhile.
   */
  function restoreFocus(anchor: FocusAnchor): boolean {
    if (!focusLost()) {
      return true;
    }

    const all = blocks();
    const current = ids();
    const uid = current.includes(anchor.uid)
      ? anchor.uid
      : current.length === anchor.count
        ? current[anchor.index]
        : undefined;

    // The block is gone, deleted rather than replaced.
    if (uid === undefined) {
      return true;
    }

    let target: Element | undefined = all.find(
      (block) => block.dataset.id === uid
    );

    for (const index of anchor.path) {
      target = target?.children[index];
    }

    if (!(target instanceof HTMLElement)) {
      return false;
    }

    target.focus({preventScroll: true});

    if (document.activeElement !== target) {
      return false;
    }

    if (
      anchor.selection &&
      (target instanceof HTMLInputElement ||
        target instanceof HTMLTextAreaElement)
    ) {
      target.setSelectionRange(...anchor.selection);
    }

    return true;
  }

  return {
    /** Lets the next rebuild scroll freely, for a block about to be revealed. */
    setRevealing(value: boolean): void {
      revealing = value;
    },
  };
}
