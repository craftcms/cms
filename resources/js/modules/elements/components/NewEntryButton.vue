<script setup lang="ts">
  import {computed} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem, ActionItemLink} from '@/common/types';
  import type {Source, SourceItem} from '@/modules/elements/types/sources';
  import useCraftData from '@/common/composables/useCraftData';
  import CreateEntryController from '@actions/Entries/CreateEntryController';
  import {withIndexReturnUrl} from '@/modules/elements/index/composables/useElementIndexVisits';
  import {
    useCreatableEntryTypes,
    type CreatableType,
    type PublishableSection,
  } from '@/modules/elements/composables/useCreatableEntryTypes';
  import {usePage} from '@inertiajs/vue3';

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
  const page = usePage();

  const {creatableTypes, menuItems: groupedMenuItems} = useCreatableEntryTypes({
    sources: () => props.sources,
    source: () => props.source,
    publishableSections: () => props.publishableSections,
    siteId: () => site.value?.id,
  });

  // The URL the legacy button used: a GET to `entries/{section}/new`, scoped to
  // an entry type and the active site, which creates a draft and redirects to
  // the editor, which returns here once it's saved.
  function createUrl({section, entryType}: CreatableType): string {
    const query: Record<string, string> = {type: entryType.handle};

    if (site.value?.id != null) {
      query.siteId = String(site.value.id);
    }

    return withIndexReturnUrl(
      CreateEntryController['/{cpTrigger?}/entries/{section}/new'].url(
        {section: section.handle},
        {query}
      ),
      page.url
    );
  }

  const menuItems = computed<Array<ActionItem>>(() =>
    groupedMenuItems(
      (creatable): ActionItemLink => ({
        type: 'link',
        href: createUrl(creatable),
        label: creatable.entryType.name,
        ...(creatable.entryType.icon
          ? {icon: creatable.entryType.icon.name}
          : {}),
        // A full navigation: the entry editor these point at is still the legacy
        // stack.
        external: true,
      })
    )
  );

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
