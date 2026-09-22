<script setup lang="ts">
  import {computed} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem, ActionItemLink} from '@/common/types';
  import type {Source, SourceItem} from '@/modules/elements/types/sources';
  import useCraftData from '@/common/composables/useCraftData';
  import CreateEntryController from '@actions/Entries/CreateEntryController';

  type EntryType = {
    handle: string;
    id: number;
    name: string;
    /** `Icons::resolveIconData()`: the resolved name and its family. */
    icon: {name: string; family?: string} | null;
  };

  export type PublishableSection = {
    /**
     * A nested resource collection, so it arrives wrapped as `{data: [...]}`
     * rather than as the bare array it is on the server.
     */
    entryTypes: Array<EntryType> | {data: Array<EntryType>};
    handle: string;
    id: number;
    name: string;
    sites: Array<number>;
    type: string;
    uid: string;
    canSave: boolean;
  };

  /** One thing the button can create: an entry type, in a section. */
  type CreatableType = {section: PublishableSection; entryType: EntryType};

  const props = defineProps<{
    // Every source on this index, to limit creation to the sections it shows.
    sources: Array<Source>;
    // The source being viewed, which narrows what's on screen further.
    source?: SourceItem;
    publishableSections: Array<PublishableSection>;
    // Singular element display name (e.g. "Entry") used for the button label.
    elementDisplayName: string;
  }>();

  const {site} = useCraftData();

  function entryTypesOf(section: PublishableSection): Array<EntryType> {
    return Array.isArray(section.entryTypes)
      ? section.entryTypes
      : (section.entryTypes?.data ?? []);
  }

  /** Sources can arrive grouped under headings; creation cares about both. */
  const sourceItems = computed<Array<SourceItem>>(() =>
    props.sources.flatMap((source) =>
      source.type === 'heading' ? (source.children ?? []) : [source]
    )
  );

  // The sections this user can publish in, shown on this index, and available
  // on the site being viewed.
  const creatableSections = computed(() => {
    const handles = new Set(
      sourceItems.value
        .map((source) => source.data?.handle)
        .filter((handle): handle is string => typeof handle === 'string')
    );

    return props.publishableSections.filter(
      (section) =>
        handles.has(section.handle) &&
        site.value?.id != null &&
        section.sites.includes(site.value.id)
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
    const data = props.source?.data ?? {};
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
      entryTypesOf(section)
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

  // The URL the legacy button used: a GET to `entries/{section}/new`, scoped to
  // an entry type and the active site, which creates a draft and redirects to
  // the editor.
  function createUrl({section, entryType}: CreatableType): string {
    const query: Record<string, string> = {type: entryType.handle};

    if (site.value?.id != null) {
      query.siteId = String(site.value.id);
    }

    return CreateEntryController['/{cpTrigger?}/entries/{section}/new'].url(
      {section: section.handle},
      {query}
    );
  }

  /** One per type, grouped under its section when more than one contributes. */
  const menuItems = computed<Array<ActionItem>>(() => {
    const link = (creatable: CreatableType): ActionItemLink => ({
      type: 'link',
      href: createUrl(creatable),
      label: creatable.entryType.name,
      ...(creatable.entryType.icon
        ? {icon: creatable.entryType.icon.name}
        : {}),
      // A full navigation: the entry editor these point at is still the legacy
      // stack.
      external: true,
    });

    const sections = [
      ...new Set(creatableTypes.value.map((creatable) => creatable.section)),
    ];

    if (sections.length < 2) {
      return creatableTypes.value.map(link);
    }

    return sections.map((section) => ({
      type: 'group' as const,
      heading: section.name,
      items: creatableTypes.value
        .filter((creatable) => creatable.section === section)
        .map(link),
    }));
  });

  const label = computed(() =>
    t('New {type}', {type: props.elementDisplayName})
  );

  // A real href, rather than a click handler, so the browser keeps the legacy
  // button's Ctrl/⌘-click "open in a new tab".
  const onlyUrl = computed(() => {
    const [only] = creatableTypes.value;

    return only ? createUrl(only) : undefined;
  });
</script>

<template>
  <!-- One entry type on screen: the button creates it. -->
  <craft-button
    v-if="creatableTypes.length === 1"
    type="button"
    :variant="ButtonVariant.Primary"
    icon="plus"
    :href="onlyUrl"
  >
    {{ label }}
  </craft-button>

  <!-- Several: the button opens a menu of them. -->
  <ActionMenu
    v-else-if="creatableTypes.length > 1"
    :actions="menuItems"
    :label="t('New entry, choose an entry type')"
  >
    <template #invoker>
      <craft-button
        slot="invoker"
        type="button"
        :variant="ButtonVariant.Primary"
        icon="plus"
      >
        {{ label }}
      </craft-button>
    </template>
  </ActionMenu>
</template>
