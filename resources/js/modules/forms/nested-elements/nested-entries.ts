import {normalizeClass} from 'vue';

export type NestedEntry = CraftCms.Cms.Element.Data.NestedElementCard;

type NestedEntryData = NonNullable<NestedEntry['cardAttributes']['data']>;

export type NestedEntryCapability = {
  [Key in keyof NestedEntryData]-?: NonNullable<
    NestedEntryData[Key]
  > extends boolean
    ? Key
    : never;
}[keyof NestedEntryData];

export type NestedEntriesManager = {
  elementType: string;
  canCreate: boolean;
  canPaste?: boolean;
  sortable?: boolean;
  maxElements?: number | null;
  createButtonLabel?: string;
  deleteConfirmationMessage?: string;
  bulkDeleteConfirmationMessage?: string;
  prevalidate?: boolean;
  createAttributes?:
    | Record<string, string | number>
    | Array<{
        label: string;
        group?: string;
        icon?: string;
        color?: string;
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
  pasteableData?: {
    attribute: string;
    values: Array<string | number>;
  } | null;
};

export type NestedEntriesProps = {
  viewMode: 'cards' | 'cards-grid';
  unavailableMessage?: string | null;
  manager: NestedEntriesManager | null;
  cards: NestedEntry[];
};

export function entryCan(
  entry: NestedEntry | undefined,
  capability: NestedEntryCapability
): boolean {
  return Boolean(entry?.cardAttributes?.data?.[capability]);
}

export function markInvalidEntries(
  entries: NestedEntry[],
  invalidIds: number[]
): NestedEntry[] {
  if (!invalidIds.length) {
    return entries;
  }

  return entries.map((entry) => ({
    ...entry,
    cardAttributes: {
      ...entry.cardAttributes,
      class: normalizeClass([
        entry.cardAttributes?.class,
        {error: invalidIds.includes(entry.id)},
      ]),
    },
  }));
}

export function editLinkTarget(
  event: MouseEvent,
  entries: NestedEntry[]
): NestedEntry | null {
  if (
    event.button !== 0 ||
    event.metaKey ||
    event.ctrlKey ||
    event.shiftKey ||
    event.altKey ||
    !(event.target instanceof Element)
  ) {
    return null;
  }

  const anchor = event.target.closest('a[href]');
  const item = event.target.closest('[data-nested-id]');
  if (!anchor || !item) {
    return null;
  }

  const id = Number(item.getAttribute('data-nested-id'));
  const entry = entries.find((candidate) => candidate.id === id);

  return entry?.editUrl &&
    entryCan(entry, 'editable') &&
    anchor.getAttribute('href') === entry.editUrl
    ? entry
    : null;
}
