import {t} from '@craftcms/ui';
import axios from 'axios';
import {normalizeClass} from 'vue';
import type {PaginationData} from '@/common/types';
import type {ContentIndexData} from '@/modules/elements/index/composables/useContentIndexData';
import type {InlineEditableRow} from '@/modules/elements/index/composables/useInlineEditing';
import type {BulkActionItem} from '@/modules/elements/types/actions';
import {craft, type CopiedElementInfo} from '@/modules/matrix/interop';

export type NestedEntry = CraftCms.Cms.Element.Data.NestedElementCard &
  InlineEditableRow &
  Record<string, unknown>;

export type NestedIndexEntry = NestedEntry & {label: string};

export type NestedEntriesManager = {
  elementType: string;
  canCreate: boolean;
  canPaste: boolean;
  sortable: boolean;
  maxElements?: number | null;
  createButtonLabel?: string;
  deleteConfirmationMessage?: string;
  bulkDeleteConfirmationMessage?: string;
  prevalidate?: boolean;
  createAttributes: Array<{
    label: string;
    group?: string | null;
    icon?: string | null;
    color?: string | null;
    attributes: Record<string, string | number>;
  }>;
  ownerElementType: string;
  ownerId: number;
  ownerIsDerivative?: boolean;
  ownerIsInDerivativeTree?: boolean;
  ownerIsUnpublishedDraft?: boolean;
  ownerSiteId: number;
  attribute: string;
  fieldId: number;
  minElements?: number | null;
  pasteableEntryTypeIds: number[];
};

export type NestedIndexOptions = {
  indexSettings?: {
    storageKey?: string | null;
    showHeaderColumn?: boolean;
    static?: boolean;
  };
  initial?: NestedContentIndexData;
};

export type NestedIndexPagination = PaginationData & {
  unfilteredTotal?: number;
};

export type NestedIndexExporter = {
  type: string;
  name: string;
  formattable: boolean;
};

export type NestedContentIndexData = Omit<
  ContentIndexData,
  'data' | 'exporters' | 'pagination' | 'actions'
> & {
  data: NestedIndexEntry[];
  exporters: NestedIndexExporter[];
  pagination: NestedIndexPagination;
  actions: BulkActionItem[] | null;
  reorderable: boolean;
  headHtml?: string;
  bodyHtml?: string;
  fieldLayouts?: Array<Record<string, string | number | boolean | null>>;
};

export type NestedEntriesProps = {
  viewMode: 'cards' | 'cards-grid' | 'index';
  unavailableMessage?: string | null;
  manager: NestedEntriesManager | null;
  cards: NestedEntry[];
  index?: NestedIndexOptions | null;
};

export function nestedOwnerParams(
  manager: NestedEntriesManager,
  ownerId: number
) {
  return {
    ownerElementType: manager.ownerElementType,
    ownerId,
    ownerSiteId: manager.ownerSiteId,
    attribute: manager.attribute,
  };
}

export function nestedCreateChoices(manager: NestedEntriesManager | null) {
  return (manager?.createAttributes ?? []).map((choice) => ({
    value: choice.attributes,
    label: choice.label,
    icon: choice.icon,
    color: choice.color,
    group: choice.group,
  }));
}

export function isPasteable(
  copied: CopiedElementInfo[],
  options: {
    elementType: string;
    entryTypeIds: number[];
    room: boolean;
    requireEntryTypeId?: boolean;
  }
): boolean {
  return (
    options.room &&
    copied.length > 0 &&
    copied.every(
      (element) =>
        element.type === options.elementType &&
        (!options.requireEntryTypeId && options.entryTypeIds.length === 0
          ? true
          : element.data?.entryTypeId !== undefined &&
            options.entryTypeIds.includes(element.data.entryTypeId))
    )
  );
}

export function nestedPasteLabel(
  elementType: string | undefined,
  count: number,
  position?: 'above' | 'before'
): string {
  const names = craft().elementTypeNames[elementType ?? ''];
  const type =
    names?.[count === 1 ? 2 : 3] ?? t(count === 1 ? 'entry' : 'entries');
  const label = position
    ? t(position === 'above' ? 'Paste {type} above' : 'Paste {type} before', {
        type,
      })
    : t('Paste {type}', {type});

  return label.charAt(0).toUpperCase() + label.slice(1);
}

export function nestedEntriesErrorMessage(
  cause: unknown,
  fallback: string
): string {
  if (axios.isAxiosError<{message?: string}>(cause)) {
    return cause.response?.data?.message ?? fallback;
  }

  return cause instanceof Error ? cause.message : fallback;
}

export function canOpenEntry(
  entry: NestedEntry | null | undefined
): entry is NestedEntry & {editUrl: string} {
  return Boolean(entry?.editUrl && entry.cardAttributes?.data?.editable);
}

export function focusNestedEntry(
  container: HTMLElement | null | undefined,
  entries: NestedEntry[],
  id: number | null,
  fallbackIndex = 0
): void {
  if (!container) {
    return;
  }

  const items = container.querySelectorAll<HTMLElement>('[data-nested-id]');
  const target =
    [...items].find((item) => Number(item.dataset.nestedId) === id) ??
    items.item(fallbackIndex);
  const entry = entries.find(
    (item) => item.id === Number(target?.dataset.nestedId)
  );
  const link = [
    ...(target?.querySelectorAll<HTMLAnchorElement>('a[href]') ?? []),
  ].find((candidate) => candidate.getAttribute('href') === entry?.editUrl);
  const button = target?.querySelector<HTMLElement>(
    '[data-edit-entry], craft-action-menu craft-button[slot="invoker"]'
  );
  (link ?? button)?.focus();
}

export function nestedEntryReorderOffset(
  entries: NestedEntry[],
  selectedIds: number[],
  from: number,
  to: number,
  pageOffset = 0
): number | null {
  const moved = entries[from];
  const target = entries[to];
  if (!moved || !target || from === to) {
    return null;
  }

  const remaining = entries.filter((entry) => !selectedIds.includes(entry.id));
  const targetIndex = remaining.findIndex((entry) => entry.id === target.id);

  return targetIndex < 0
    ? null
    : pageOffset + targetIndex + (to > from ? 1 : 0);
}

export function markInvalidEntries<Entry extends NestedEntry>(
  entries: Entry[],
  invalidIds: number[]
): Entry[] {
  if (!invalidIds.length) {
    return entries;
  }

  return entries.map(
    (entry) =>
      ({
        ...entry,
        cardAttributes: {
          ...entry.cardAttributes,
          class: normalizeClass([
            entry.cardAttributes?.class,
            {error: invalidIds.includes(entry.id)},
          ]),
        },
      }) as Entry
  );
}
