import {computed, type ComputedRef} from 'vue';
import type {
  ActionItem,
  ActionItemButton,
  ActionItemLink,
} from '@/common/types';
import type {Source, SourceItem} from '@/modules/elements/types/sources';

export type EntryType = {
  handle: string;
  id: number;
  name: string;
  /** `Icons::resolveIconData()`: the resolved name and its family. */
  icon: {name: string; family?: string} | null;
};

export type PublishableSection = {
  /** The entry types the user can create in each site, keyed by site ID. */
  entryTypesBySite: Record<number, Array<EntryType>>;
  handle: string;
  id: number;
  name: string;
  sites: Array<number>;
  type: string;
  uid: string;
  canSave: boolean;
};

/** One thing a New entry button can create: an entry type, in a section. */
export type CreatableType = {section: PublishableSection; entryType: EntryType};

interface Options {
  /** Every source on the index, to limit creation to the sections it shows. */
  sources: () => Array<Source>;
  /** The source being viewed, which narrows what's on screen further. */
  source: () => SourceItem | null | undefined;
  publishableSections: () => Array<PublishableSection>;
  /** The site the index is showing. */
  siteId: () => number | null | undefined;
}

/**
 * The entry types an entry index offers to create: those of the sections the
 * user can publish in that the index shows, on the site it's showing.
 */
export function useCreatableEntryTypes(options: Options): {
  creatableTypes: ComputedRef<Array<CreatableType>>;
  menuItems: (
    toItem: (creatable: CreatableType) => ActionItemButton | ActionItemLink
  ) => Array<ActionItem>;
} {
  /** Sources can arrive grouped under headings; creation cares about both. */
  const sourceItems = computed<Array<SourceItem>>(() =>
    options
      .sources()
      .flatMap((source) =>
        source.type === 'heading' ? (source.children ?? []) : [source]
      )
  );

  // The sections this user can publish in, shown on this index, and available
  // on the site being viewed.
  const creatableSections = computed(() => {
    const siteId = options.siteId();
    const handles = new Set(
      sourceItems.value
        .map((source) => source.data?.handle)
        .filter((handle): handle is string => typeof handle === 'string')
    );

    return options
      .publishableSections()
      .filter(
        (section) =>
          handles.has(section.handle) &&
          siteId != null &&
          section.sites.includes(siteId)
      );
  });

  /**
   * The entry types on screen, which is what the button can create.
   *
   * A section source shows its own section; anything else — the "all entries"
   * view, a custom source — shows every creatable section on the index. A
   * source's entry type ids narrow that to the types it lists, and a custom
   * source pinned to one entry type narrows it to that one.
   */
  const creatableTypes = computed<Array<CreatableType>>(() => {
    const data = options.source()?.data ?? {};
    const sectionHandle = typeof data.handle === 'string' ? data.handle : null;
    const pinnedHandle =
      typeof data['entry-type'] === 'string' ? data['entry-type'] : null;
    const typeIds = Array.isArray(data['entry-type-ids'])
      ? data['entry-type-ids'].map(Number)
      : null;

    const ownSection = creatableSections.value.find(
      (section) => section.handle === sectionHandle
    );
    const sections = ownSection ? [ownSection] : creatableSections.value;

    return sections.flatMap((section) =>
      (section.entryTypesBySite[options.siteId()!] ?? [])
        .filter(
          (entryType) => !pinnedHandle || entryType.handle === pinnedHandle
        )
        .filter(
          (entryType) =>
            !ownSection || !typeIds || typeIds.includes(entryType.id)
        )
        .map((entryType) => ({section, entryType}))
    );
  });

  /** One item per type, grouped under its section when more than one contributes. */
  function menuItems(
    toItem: (creatable: CreatableType) => ActionItemButton | ActionItemLink
  ): Array<ActionItem> {
    const sections = [
      ...new Set(creatableTypes.value.map((creatable) => creatable.section)),
    ];

    if (sections.length < 2) {
      return creatableTypes.value.map(toItem);
    }

    return sections.map((section) => ({
      type: 'group' as const,
      heading: section.name,
      items: creatableTypes.value
        .filter((creatable) => creatable.section === section)
        .map(toItem),
    }));
  }

  return {creatableTypes, menuItems};
}
