import {isHttpError, t} from '@craftcms/ui';
import {normalizeClass} from 'vue';
import type {PaginationData} from '@/common/types';
import type {QueryParams} from '@/common/types/query';
import type {ContentIndexData} from '@/modules/elements/index/composables/useContentIndexData';
import type {InlineEditableRow} from '@/modules/elements/index/composables/useInlineEditing';
import type {BulkActionItem} from '@/modules/elements/types/actions';
import {craft, type CopiedElementInfo} from '@/modules/matrix/interop';

export type NestedElement = CraftCms.Cms.Element.Data.NestedElementCard &
  InlineEditableRow &
  Record<string, unknown>;

export type NestedIndexElement = NestedElement & {label: string};

export type NestedElementsManager = {
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
  ownerHasDrafts?: boolean;
  ownerSiteId: number;
  attribute: string;
  fieldId: number;
  minElements?: number | null;
  pasteableData?: NestedPasteableData | null;
};

/**
 * Restricts which copied elements can be pasted: each one's `data[attribute]`
 * must be one of `values` (e.g. a Matrix field's entry type IDs).
 */
export type NestedPasteableData = {
  attribute: string;
  values: Array<string | number>;
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
  data: NestedIndexElement[];
  exporters: NestedIndexExporter[];
  pagination: NestedIndexPagination;
  actions: BulkActionItem[] | null;
  reorderable: boolean;
  headHtml?: string;
  bodyHtml?: string;
  fieldLayouts?: QueryParams[];
};

export type NestedElementsProps = {
  viewMode: 'cards' | 'cards-grid' | 'index';
  unavailableMessage?: string | null;
  manager: NestedElementsManager | null;
  cards: NestedElement[];
  index?: NestedIndexOptions | null;
};

export function nestedOwnerParams(
  manager: NestedElementsManager,
  ownerId: number
) {
  return {
    ownerElementType: manager.ownerElementType,
    ownerId,
    ownerSiteId: manager.ownerSiteId,
    attribute: manager.attribute,
  };
}

export function nestedIndexParams(
  manager: NestedElementsManager,
  ownerId: number,
  initial?: NestedContentIndexData
) {
  return {
    ...nestedOwnerParams(manager, ownerId),
    ...(initial
      ? {
          sortable: manager.sortable,
          canPaste: manager.canPaste,
          static: initial.viewState.static ?? false,
          showHeaderColumn: initial.viewState.showHeaderColumn,
          per_page: initial.pagination.per_page,
          allowedViewModes: initial.viewModes.map(({mode}) => mode),
          defaultTableColumns: initial.defaultTableColumns,
          fieldLayouts: initial.fieldLayouts ?? [],
        }
      : {}),
  };
}

export function nestedCreateChoices(manager: NestedElementsManager | null) {
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
    pasteableData?: NestedPasteableData | null;
    room: boolean;
  }
): boolean {
  const constraint = options.pasteableData;

  return (
    options.room &&
    copied.length > 0 &&
    copied.every(
      (element) =>
        element.type === options.elementType &&
        (!constraint ||
          isAllowedValue(element.data?.[constraint.attribute], constraint))
    )
  );
}

/** Server-supplied IDs can arrive as strings, so values compare by their string form. */
function isAllowedValue(
  value: unknown,
  constraint: NestedPasteableData
): boolean {
  return (
    (typeof value === 'string' || typeof value === 'number') &&
    constraint.values.some((allowed) => String(allowed) === String(value))
  );
}

export function nestedPasteLabel(
  elementType: string | undefined,
  count: number,
  position?: 'above' | 'before'
): string {
  const names = craft().elementTypeNames[elementType ?? ''];
  const type =
    names?.[count === 1 ? 2 : 3] ?? t(count === 1 ? 'element' : 'elements');
  const label = position
    ? t(position === 'above' ? 'Paste {type} above' : 'Paste {type} before', {
        type,
      })
    : t('Paste {type}', {type});

  return label.charAt(0).toUpperCase() + label.slice(1);
}

export function nestedElementsErrorMessage(
  cause: unknown,
  fallback: string
): string {
  if (isHttpError<{message?: string}>(cause)) {
    return cause.response?.data?.message ?? fallback;
  }

  return cause instanceof Error ? cause.message : fallback;
}

export function canOpenElement(
  element: NestedElement | null | undefined
): element is NestedElement & {editUrl: string} {
  return Boolean(element?.editUrl && element.cardAttributes?.data?.editable);
}

export function focusNestedElement(
  container: HTMLElement | null | undefined,
  elements: NestedElement[],
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
  const element = elements.find(
    (item) => item.id === Number(target?.dataset.nestedId)
  );
  const link = [
    ...(target?.querySelectorAll<HTMLAnchorElement>('a[href]') ?? []),
  ].find((candidate) => candidate.getAttribute('href') === element?.editUrl);
  const button = target?.querySelector<HTMLElement>(
    '[data-edit-element], craft-action-menu craft-button[slot="invoker"]'
  );
  (link ?? button)?.focus();
}

export function nestedElementReorderOffset(
  elements: NestedElement[],
  selectedIds: number[],
  from: number,
  to: number,
  pageOffset = 0
): number | null {
  const moved = elements[from];
  const target = elements[to];
  if (!moved || !target || from === to) {
    return null;
  }

  const remaining = elements.filter(
    (element) => !selectedIds.includes(element.id)
  );
  const targetIndex = remaining.findIndex(
    (element) => element.id === target.id
  );

  return targetIndex < 0
    ? null
    : pageOffset + targetIndex + (to > from ? 1 : 0);
}

export function markInvalidElements<Entry extends NestedElement>(
  elements: Entry[],
  invalidIds: number[]
): Entry[] {
  if (!invalidIds.length) {
    return elements;
  }

  return elements.map(
    (element) =>
      ({
        ...element,
        cardAttributes: {
          ...element.cardAttributes,
          class: normalizeClass([
            element.cardAttributes?.class,
            {error: invalidIds.includes(element.id)},
          ]),
        },
      }) as Entry
  );
}
