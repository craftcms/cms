import {nextTick, watch, type Ref, type WatchSource} from 'vue';

/** Where focus was in a block, by its place rather than its element. */
interface FocusAnchor {
  uid: string;
  index: number;
  count: number;
  /** Child indexes from the block down to the focused element. */
  path: number[];
  selection: [number, number] | null;
  /** The tab strips in the block that had moved off their first tab. */
  tabs: Array<{path: number[]; index: number}>;
}

/** A `craft-tabs` strip, which owns which of its tabs is selected. */
type TabStrip = HTMLElement & {
  selectedIndex: number;
  updateComplete: Promise<unknown>;
};

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
 * the blocks themselves are renamed onto it. The rebuilt block shows its first
 * tabs, so the ones it had selected are selected again first.
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

    void nextTick(async () => {
      for (const [node, top] of anchors) {
        if (node.scrollTop !== top) {
          node.scrollTop = top;
        }
      }

      const anchor = focus;

      // A later rebuild may have taken over while the tabs were selected.
      if (anchor && (await restoreFocus(anchor)) && focus === anchor) {
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

    const path = pathWithin(block, active);

    if (!path) {
      return null;
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
      tabs: [...block.querySelectorAll<TabStrip>('craft-tabs')].flatMap(
        (strip) => {
          const stripPath = pathWithin(block, strip);

          return strip.selectedIndex && stripPath
            ? [{path: stripPath, index: strip.selectedIndex}]
            : [];
        }
      ),
    };
  }

  /** Child indexes from `root` down to `node`. */
  function pathWithin(root: Element, node: Element): number[] | null {
    const path: number[] = [];

    for (let current = node; current !== root;) {
      const parent = current.parentElement;

      if (!parent) {
        return null;
      }

      path.unshift([...parent.children].indexOf(current));
      current = parent;
    }

    return path;
  }

  function nodeAt(root: Element, path: number[]): Element | undefined {
    let node: Element | undefined = root;

    for (const index of path) {
      node = node?.children[index];
    }

    return node;
  }

  /**
   * Puts focus back, and reports whether there's nothing left to do: it's
   * back, or has gone somewhere else meanwhile.
   */
  async function restoreFocus(anchor: FocusAnchor): Promise<boolean> {
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

    const block = all.find((block) => block.dataset.id === uid);

    if (!block) {
      return false;
    }

    // Outer strips first, as selecting a tab renders the strips inside it.
    for (const tab of anchor.tabs) {
      const strip = nodeAt(block, tab.path) as TabStrip | undefined;

      if (strip?.localName !== 'craft-tabs') {
        return false;
      }

      // Selecting one before its first render wouldn't be announced, and the
      // fields wouldn't follow.
      await strip.updateComplete;

      if (strip.selectedIndex !== tab.index) {
        strip.selectedIndex = tab.index;
        await strip.updateComplete;
        await nextTick();
      }
    }

    if (!focusLost()) {
      return true;
    }

    const target = nodeAt(block, anchor.path);

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
