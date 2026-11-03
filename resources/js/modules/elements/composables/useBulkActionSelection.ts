import {computed, toValue, type MaybeRefOrGetter} from 'vue';
import {useEventListener} from '@vueuse/core';
import {copyElements} from '@/modules/elements/index/copy-elements';
import type {
  BulkActionItem,
  ElementActionSelection,
} from '@/modules/elements/types/actions';

/** A window event fired by one of the bar's own action items. */
export type OwnedBulkActionEvent = CustomEvent<{
  trigger: HTMLElement;
  elementIds?: ReadonlyArray<string | number>;
  elementType?: string;
  elements?: ReadonlyArray<ElementActionSelection>;
}>;

/**
 * The selection a bulk-actions bar acts on: what kind of rows it holds, which
 * actions apply to it, and the window events its action items fire.
 *
 * Every bar on the page hears the same window events, so a bar only answers
 * those fired from inside `root`.
 */
export function useBulkActionSelection(options: {
  selectedIds: MaybeRefOrGetter<ReadonlyArray<string | number>>;
  /** The bar's root element. */
  root: MaybeRefOrGetter<HTMLElement | null | undefined>;
  /** The element type copied rows are, when the event doesn't say. */
  elementType: MaybeRefOrGetter<string | undefined>;
  /** Selected row data, so copies carry each element's site, owner and draft. */
  selectedElements?: MaybeRefOrGetter<ReadonlyArray<ElementActionSelection>>;
}) {
  const selectedIds = computed(() => toValue(options.selectedIds));
  const selectedCount = computed(() => selectedIds.value.length);

  /** Only meaningful for a real element index, where a selection can mix elements and asset-folder rows. */
  const selectionType = computed<'elements' | 'folders' | 'mixed'>(() => {
    const folderCount = selectedIds.value.filter((id) =>
      String(id).startsWith('folder:')
    ).length;

    if (folderCount === 0) {
      return 'elements';
    }

    return folderCount === selectedCount.value ? 'folders' : 'mixed';
  });

  function appliesToSelection(
    item: Pick<BulkActionItem, 'appliesTo'>
  ): boolean {
    return !item.appliesTo || item.appliesTo === selectionType.value;
  }

  function owns(event: Event): event is OwnedBulkActionEvent {
    if (!(event instanceof CustomEvent)) {
      return false;
    }

    const trigger = event.detail?.trigger;

    return (
      trigger instanceof HTMLElement &&
      Boolean(toValue(options.root)?.contains(trigger))
    );
  }

  /** Listens for a window event fired by one of this bar's action items. */
  function onOwnedEvent(
    name: string,
    handler: (event: OwnedBulkActionEvent) => void
  ): void {
    useEventListener(window, name, (event: Event) => {
      if (owns(event)) {
        handler(event);
      }
    });
  }

  /** The menu invoker an action item was chosen from, to return focus to. */
  function invokerFor(trigger: HTMLElement): HTMLElement {
    const menu = trigger.closest('craft-action-menu');

    return menu?.querySelector<HTMLElement>('[slot="invoker"]') ?? trigger;
  }

  /**
   * Copy mirrors Craft 5: the selection is handed to the legacy
   * `Craft.cp.copyElements()`, which stores it in localStorage (for paste
   * targets like nested element managers), shows the persistent "copied"
   * notification, and broadcasts to other tabs. Nothing changes server-side,
   * so no refresh/`performed` is emitted.
   */
  onOwnedEvent('craft:copy-elements', (event) => {
    const detail = event.detail ?? {};

    copyElements(
      detail.elementType ?? toValue(options.elementType) ?? '',
      detail.elementIds ?? selectedIds.value,
      detail.elements ?? toValue(options.selectedElements) ?? []
    );
  });

  return {
    selectedCount,
    appliesToSelection,
    onOwnedEvent,
    invokerFor,
  };
}
