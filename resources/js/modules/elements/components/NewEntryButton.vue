<script setup lang="ts">
  import {computed} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import type {Source, SourceItem} from '@/modules/elements/types/sources';
  import useCraftData from '@/common/composables/useCraftData';
  import CreateEntryController from '@actions/Entries/CreateEntryController';

  export type PublishableSection = {
    entryTypes: Array<{
      handle: string;
      id: number;
      name: string;
      icon: string | null;
    }>;
    handle: string;
    id: number;
    name: string;
    sites: Array<number>;
    type: string;
    uid: string;
    canSave: boolean;
  };

  const props = defineProps<{
    // The full source list, used to limit creation to the sections actually
    // shown on this index.
    sources: Array<Source>;
    // The active source; when it maps to a creatable section the primary button
    // defaults to it.
    source?: SourceItem;
    publishableSections: Array<PublishableSection>;
    // Singular element display name (e.g. "Entry") used for the button label.
    elementDisplayName: string;
  }>();

  const {site} = useCraftData();

  // The sections the current user can create entries in, limited to the ones
  // shown as sources and available on the active site — mirrors the gating the
  // legacy EntryIndex applied before rendering its "New entry" button.
  const creatableSections = computed(() => {
    const sourceHandles = props.sources
      .filter((source) => source.type !== 'heading')
      .map((source) => source.data?.handle)
      .filter((handle): handle is string => !!handle);

    return props.publishableSections
      .filter((section) => site?.id != null && section.sites.includes(site.id))
      .filter((section) => sourceHandles.includes(section.handle));
  });

  // The creatable section matching the active source, or null when the source
  // isn't a single creatable section (e.g. the "all entries" view or Singles).
  // This is what makes the button default to the section you're viewing.
  const currentSection = computed(
    () =>
      creatableSections.value.find(
        (section) => section.handle === props.source?.data?.handle
      ) ?? null
  );

  // Custom sources can be pinned to a single entry type; when so, the primary
  // button should create that type.
  const currentEntryTypeHandle = computed<string | undefined>(() => {
    const handle = props.source?.data?.['entry-type'];
    return Object(handle).constructor === String ? String(handle) : undefined;
  });

  // Builds the create-entry URL the legacy button used: a GET request to
  // `entries/{section}/new` (optionally scoped to an entry type + the active
  // site) that creates a draft and redirects to the editor.
  function createUrl(sectionHandle: string, entryTypeHandle?: string): string {
    const query: Record<string, string> = {};
    if (entryTypeHandle) {
      query.type = entryTypeHandle;
    }
    if (site?.id != null) {
      query.siteId = String(site.id);
    }

    return CreateEntryController['/{cpTrigger?}/entries/{section}/new'].url(
      {section: sectionHandle},
      Object.keys(query).length ? {query} : undefined
    );
  }

  // Sections shown in the dropdown: on a specific section it's the *other*
  // creatable sections; on the "all entries" view it's all of them.
  const menuSections = computed(() =>
    currentSection.value
      ? creatableSections.value.filter(
          (section) => section.handle !== currentSection.value!.handle
        )
      : creatableSections.value
  );

  // Each dropdown entry is a section-level "New {section} entry" link, matching
  // the legacy menu (the editor lets you switch entry type after creation).
  const createMenuItems = computed<ActionItem[]>(() =>
    menuSections.value.map(
      (section): ActionItem => ({
        type: 'link',
        href: createUrl(section.handle),
        label: t('New {section} entry', {section: section.name}),
      })
    )
  );

  // Navigate to the create URL for the active section, preserving the legacy
  // button's Ctrl/⌘-click "open in a new tab" affordance.
  function createInCurrentSection(event: MouseEvent) {
    const section = currentSection.value;
    if (!section) {
      return;
    }

    const href = createUrl(section.handle, currentEntryTypeHandle.value);

    if (event.metaKey || event.ctrlKey) {
      window.open(href);
    } else {
      window.location.href = href;
    }
  }
</script>

<template>
  <!-- Viewing a section you can publish in: a primary "New entry" that defaults
       to that section, plus a menu of the other sections. -->
  <craft-button-group v-if="currentSection && menuSections.length">
    <craft-button
      type="button"
      variant="accent"
      icon="plus"
      @click="createInCurrentSection"
    >
      {{ t('New {type}', {type: elementDisplayName}) }}
    </craft-button>
    <ActionMenu
      icon="chevron-down"
      :actions="createMenuItems"
      :label="t('New entry, choose a section')"
    >
      <template #invoker="{label}">
        <craft-button slot="invoker" type="button" variant="accent" icon>
          <craft-icon name="chevron-down" :label="label"></craft-icon>
        </craft-button>
      </template>
    </ActionMenu>
  </craft-button-group>

  <!-- The current section is the only one you can publish in: just the primary
       button. -->
  <craft-button
    v-else-if="currentSection"
    type="button"
    variant="accent"
    icon="plus"
    @click="createInCurrentSection"
  >
    {{ t('New {type}', {type: elementDisplayName}) }}
  </craft-button>

  <!-- The "all entries" view (or Singles): only a menu of creatable sections,
       defaulting to none. -->
  <ActionMenu
    v-else-if="creatableSections.length"
    icon="chevron-down"
    :actions="createMenuItems"
    :label="t('New entry, choose a section')"
  >
    <template #invoker>
      <craft-button
        slot="invoker"
        type="button"
        :variant="ButtonVariant.Primary"
        icon="plus"
      >
        {{ t('New {type}', {type: elementDisplayName}) }}
      </craft-button>
    </template>
  </ActionMenu>
</template>
