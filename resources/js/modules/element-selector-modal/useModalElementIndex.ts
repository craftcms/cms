import {actionClient, type ElementInfo} from '@craftcms/ui';
import {computed} from 'vue';
import {
  type ContentIndexData,
  type ElementIndexRow,
} from '@/modules/elements/index/composables/useContentIndexData';
import {useDetachedElementIndex} from '@/modules/elements/index/composables/useDetachedElementIndex';

type Row = ElementIndexRow;

/**
 * What the modal hands back for each selected row.
 *
 * An alias of the core's `ElementInfo` rather than a second declaration of the
 * same shape — the controller, the web component and this composable all have to
 * agree on it, and two copies would drift.
 */
export type SelectedElement = ElementInfo;

/**
 * The selection metadata `ModalIndexViewModel::extraRowData()` nests on each row.
 *
 * Nested, not merged: the rest of a row is rendered column HTML keyed by
 * attribute, and a flat merge let a column overwrite anything sharing its name —
 * `status` arrived as a `<craft-badge>` element rather than `"pending"`. Reading
 * one key also means no whitelist to keep in step with the server.
 */
function elementInfo(row: Row): Record<string, unknown> {
  return (row.elementInfo as Record<string, unknown> | undefined) ?? {};
}

interface Options {
  /** The `element-selector-modals/body` action path. */
  action: string;
  /** The payload the modal opened with, so the first render needs no request. */
  initial: ContentIndexData;
  /** Params identifying the index — element type, sources, criteria, condition. */
  params: Record<string, unknown>;
  /** Element ids that can't be selected (already related, self-relation). */
  disabledElementIds?: () => number[];
}

/** Adds modal fetching and selection rules to the shared detached index. */
export function useModalElementIndex(options: Options) {
  const disabledIds = computed(
    () => new Set(options.disabledElementIds?.() ?? [])
  );
  const index = useDetachedElementIndex({
    initial: options.initial,
    fetch: async (query) => {
      const {data} = await actionClient.post(options.action, {
        ...options.params,
        ...query,
      });

      return data.props as ContentIndexData;
    },
    enableRowSelection: (row) =>
      !row.isFolder && !disabledIds.value.has(Number(row.id)),
  });
  const {table} = index.view;

  /**
   * The selected rows, in the shape the relation field expects.
   *
   * Mirrors what `Craft.getElementInfo()` read off each chip's data attributes,
   * because `onModalSelect` and `app/render-elements` both consume it.
   */
  const selectedElements = computed<SelectedElement[]>(() =>
    table.getSelectedRowModel().rows.map(({original}) => {
      const info = elementInfo(original);

      return {
        // Type-specific extras first — `kind`/`alt` for assets, `folderId` for
        // folders — so the named keys below always win.
        ...info,
        id: Number(original.id),
        siteId: info.siteId != null ? Number(info.siteId) : null,
        label:
          typeof info.label === 'string' || typeof info.label === 'number'
            ? String(info.label)
            : String(original.id),
        status: (info.status as string | null) ?? null,
        url: (info.url as string | null) ?? null,
        hasThumb: Boolean(info.hasThumb),
      };
    })
  );

  const hasSelection = computed(() => selectedElements.value.length > 0);

  return {
    ...index,
    selectedElements,
    hasSelection,
  };
}
