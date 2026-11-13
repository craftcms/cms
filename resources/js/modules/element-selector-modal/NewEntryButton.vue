<script setup lang="ts">
  import {computed} from 'vue';
  import {
    actionClient,
    t,
    type ElementInfo,
    type ElementSelectorController,
  } from '@craftcms/ui';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {openSlideout} from '@/common/slideouts';
  import EditElementController from '@/actions/CraftCms/Cms/Http/Controllers/Elements/EditElementController';
  import CreateEntryController from '@actions/Entries/CreateEntryController';
  import {
    useCreatableEntryTypes,
    type CreatableType,
    type PublishableSection,
  } from '@/modules/elements/composables/useCreatableEntryTypes';
  import type {Source, SourceItem} from '@/modules/elements/types/sources';

  const props = defineProps<{
    controller: ElementSelectorController;
    sources: Array<Source>;
    source: SourceItem | null;
    siteId: number | null;
    /** Where focus goes once the entry's slideout closes. */
    opener?: () => HTMLElement | null;
  }>();

  /**
   * Read from `window.Craft` rather than the Inertia page: the modal mounts as
   * its own app, and opens on legacy pages that have no Inertia page at all.
   */
  const {creatableTypes, menuItems} = useCreatableEntryTypes({
    sources: () => props.sources,
    source: () => props.source,
    publishableSections: () =>
      (window.Craft?.publishableSections as PublishableSection[]) ?? [],
    siteId: () => props.siteId,
  });

  const label = computed(() => {
    const sections = new Set(creatableTypes.value.map((c) => c.section));
    const [section] = sections;

    return sections.size === 1
      ? t('New {section} entry', {section: section!.name})
      : t('New entry');
  });

  /**
   * Creates the entry and opens it, then chooses it once it's saved.
   *
   * The modal closes first: it's a top-layer dialog, which a slideout can't
   * open over. Choosing then goes to the opener's `onSelect` directly, as
   * submitting the modal would have, so a relation field relates the entry.
   */
  async function create({section, entryType}: CreatableType): Promise<void> {
    const {controller, siteId} = props;
    const onSelect = controller.options.onSelect;
    const opener = props.opener?.() ?? null;

    controller.setBusy(true);

    let entry: {id: number; draftId: number; title: string | null};

    try {
      const {data} = await actionClient.post(
        CreateEntryController[
          '/{cpTrigger?}/{actionTrigger?}/entries/create'
        ].url(),
        {siteId, section: section.handle, type: entryType.handle}
      );
      entry = data.entry;
    } finally {
      controller.setBusy(false);
    }

    controller.close();

    void openSlideout(
      EditElementController['/{cpTrigger?}/{actionTrigger?}/elements/edit'].url(
        undefined,
        {
          query: {
            elementType: controller.elementType,
            elementId: entry.id,
            draftId: entry.draftId,
            siteId,
            fresh: 1,
          },
        }
      ),
      {
        opener,
        onSaved: ({draft, data}) => {
          if (draft) {
            return;
          }

          const saved = (data?.element ?? {}) as {title?: string | null};
          const info: ElementInfo = {
            id: entry.id,
            siteId,
            label: saved.title || entry.title || String(entry.id),
            status: null,
            url: null,
            hasThumb: false,
          };

          void onSelect?.([info], {});
        },
      }
    );
  }

  const actions = computed<Array<ActionItem>>(() =>
    menuItems((creatable) => ({
      label: creatable.entryType.name,
      ...(creatable.entryType.icon
        ? {icon: creatable.entryType.icon.name}
        : {}),
      onClick: () => void create(creatable),
    }))
  );
</script>

<template>
  <!-- One entry type on screen: the button creates it. -->
  <craft-button
    v-if="creatableTypes.length === 1"
    type="button"
    icon="plus"
    @click="create(creatableTypes[0]!)"
  >
    {{ label }}
  </craft-button>

  <!-- Several: the button opens a menu of them. -->
  <ActionMenu
    v-else-if="creatableTypes.length > 1"
    :actions="actions"
    :label="t('New entry, choose an entry type')"
  >
    <template #invoker>
      <craft-button slot="invoker" type="button" icon="plus">
        {{ label }}
      </craft-button>
    </template>
  </ActionMenu>
</template>
