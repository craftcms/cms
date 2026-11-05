import {nextTick, onMounted, ref, toRaw, type Ref} from 'vue';
import {
  isBlockCollapsed,
  setBlockCollapsed,
} from '@/modules/matrix/collapsed-blocks';
import {blockPreviewParts} from '@/modules/matrix/preview-text';
import {NESTED_ELEMENT_UID_PREFIX, type NestedElementValue} from '../types';
import type {UpdateBlocksValue} from './nested-element-blocks';

type BlockCollapseOptions = {
  value: () => NestedElementValue;
  siteId: () => number | null | undefined;
  host: Readonly<Ref<HTMLElement | undefined>>;
  isDisabled: (uid: string) => boolean;
  update: UpdateBlocksValue;
};

/**
 * Collapsed blocks live in localStorage, not the value — it's a view
 * preference, and posting it would mark the ui dirty just for collapsing
 * something. Craft 5 did the same. Reads through `collapsed-blocks` are
 * reactive, so any write to it re-renders the blocks it affects.
 */
export function useBlockCollapse(options: BlockCollapseOptions) {
  /**
   * What each folded-up block says about itself when it has no UI label, taken
   * off its inputs as it folds — the same moment Craft 5 took it, and the only
   * one where the fields are still on screen to read.
   */
  const previews = ref(new Map<string, string[]>());

  function isCollapsed(uid: string): boolean {
    return isBlockCollapsed(uid, options.siteId());
  }

  /** The summary taken off a folded-up block's fields. */
  function preview(uid: string): string {
    return (previews.value.get(uid) ?? []).join(' | ');
  }

  /**
   * Folds several blocks at once. One emit for the lot: a per-block emit would
   * have each one overwrite the last, since the Control's value is written back
   * whole at its own path.
   */
  function setCollapsed(uids: readonly string[], collapsed: boolean): void {
    const next = structuredClone(toRaw(options.value()));
    let posts = false;

    for (const uid of uids) {
      setBlockCollapsed(uid, collapsed, options.siteId());
      capturePreview(uid, collapsed);

      // A block the browser minted isn't in storage under an identity the server
      // knows yet, so its state also rides along in the posted value — the same
      // job Craft 5's hidden `[collapsed]` input did for new blocks.
      if (uid.startsWith(NESTED_ELEMENT_UID_PREFIX) && next.entries[uid]) {
        next.entries[uid].collapsed = collapsed;
        posts = true;
      }
    }

    if (posts) {
      options.update(next);
    }
  }

  /** Reads (or drops) a block's summary as it folds up or opens out. */
  function capturePreview(uid: string, collapsed: boolean): void {
    if (!collapsed) {
      previews.value.delete(uid);

      return;
    }

    // The first fields container under the block is its own; the ones after it belong
    // to whatever the block nests.
    const fields = options.host.value
      ?.querySelector(`[data-id="${CSS.escape(uid)}"]`)
      ?.querySelector<HTMLElement>('[data-matrix-block-fields]');

    previews.value.set(uid, fields ? blockPreviewParts(fields) : []);
  }

  /**
   * Double-clicking a block's titlebar folds it, as it did in Craft 5. The
   * controls sharing the titlebar keep their own double-clicks.
   */
  /** Whether an event on a titlebar landed on one of its controls. */
  function isTitlebarControl(event: MouseEvent): boolean {
    return (
      event.target instanceof Element &&
      event.target.closest(
        'button, a, input, craft-checkbox, craft-action-menu, [data-drag-handle]'
      ) !== null
    );
  }

  function toggleFromTitlebar(uid: string, event: MouseEvent): void {
    if (isTitlebarControl(event)) {
      return;
    }
    event.preventDefault();
    setCollapsed([uid], !isCollapsed(uid));
  }

  /**
   * Keeps a double-click that folds a block from selecting text too. The
   * browser selects on the second press, before the `dblclick`, so it's that
   * press that's canceled.
   */
  function preventTitlebarSelection(_uid: string, event: MouseEvent): void {
    if (event.detail > 1 && !isTitlebarControl(event)) {
      event.preventDefault();
    }
  }

  onMounted(() => {
    const value = options.value();

    for (const uid of value.sortOrder) {
      // A disabled block isn't being edited, so it opens out of the way. It can
      // still be expanded from its menu — this only decides where it starts.
      const startsCollapsed =
        value.entries[uid]?.collapsed || options.isDisabled(uid);

      if (startsCollapsed && !isBlockCollapsed(uid, options.siteId())) {
        setBlockCollapsed(uid, true, options.siteId());
      }
    }

    // A block that opens folded up still needs its summary, and its fields are
    // rendered but hidden — so they're there to read once Vue has laid them out.
    void nextTick(() => {
      for (const uid of options.value().sortOrder) {
        if (isBlockCollapsed(uid, options.siteId())) {
          capturePreview(uid, true);
        }
      }
    });
  });

  return {
    isCollapsed,
    preview,
    setCollapsed,
    toggleFromTitlebar,
    preventTitlebarSelection,
  };
}
