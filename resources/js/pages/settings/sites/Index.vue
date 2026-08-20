<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {TextExpanderTriggers} from '@craftcms/ui/components/text-expander/text-expander';
  import CalloutReadOnly from '@/common/components/CalloutReadOnly.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {computed, h, nextTick, ref, watch} from 'vue';
  import type {Site, SiteGroup} from '@/common/types';
  import ModalForm from '@/common/components/ModalForm.vue';
  import {Deferred, router, useForm} from '@inertiajs/vue3';
  import {destroy, store} from '@actions/Settings/SiteGroupsController.js';
  import {create, edit, reorder} from '@actions/Settings/SitesController';
  import DeleteSiteButton from '@/modules/sites/components/DeleteSiteButton.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import Badge from '@/common/components/Badge.vue';
  import CraftInput from '@craftcms/ui/vue/CraftInput.vue';
  import useCraftData from '@/common/composables/useCraftData';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import Empty from '@/common/components/Empty.vue';
  import {useAppLayout} from '@/common/composables/useAppLayout';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

  const props = defineProps<{
    title: string;
    group: SiteGroup | null;
    groups: Array<SiteGroup>;
    sites: Array<Site>;
    nameTextExpanderTriggers?: TextExpanderTriggers;
    flash: {
      success: string | null;
      error: string | null;
    };
  }>();

  const modalActive = ref(false);
  const columnHelper = createCraftColumnHelper<Site>();
  const {readOnly} = useCraftData();

  const form = useForm({
    id: props.group?.id ?? null,
    name: props.group?.name ?? '',
  });

  function saveGroup() {
    form.clearErrors().submit(store(), {
      onSuccess: () => {
        modalActive.value = false;
        form.reset();
      },
    });
  }

  function openModal(mode: 'create' | 'update') {
    if (mode === 'create') {
      form.name = '';
      form.id = null;
    } else if (mode === 'update') {
      form.name = props.group?.rawName ?? props.group?.name ?? '';
      form.id = props.group?.id ?? null;
    }

    modalActive.value = true;
  }

  const siteIds = ref(props.sites.map((site) => site.id));
  const sites = computed((): Site[] => {
    if (siteIds.value.length > 0) {
      return siteIds.value
        .map((id) => props.sites.find((site) => site.id === id))
        .filter((s): s is Site => s !== undefined);
    }

    return [];
  });

  watch(siteIds, (newValue, oldValue) => {
    // Defer to next tick to avoid issues during drag-and-drop event handling
    nextTick(() => {
      router.post(
        reorder(),
        {
          ids: [...newValue], // Copy to ensure we have the current value
        },
        {
          preserveScroll: true,
          preserveState: true,
          onError: () => {
            siteIds.value = oldValue;
          },
        }
      );
    });
  });

  function handleReorder(startIndex: number, finishIndex: number) {
    const newIds = [...siteIds.value];
    const [id] = newIds.splice(startIndex, 1);
    if (id === undefined) return;
    newIds.splice(finishIndex, 0, id);
    siteIds.value = newIds;
  }

  const columns = ref([
    columnHelper.accessor('name', {
      header: () => t('Name'),
      cell: ({row, getValue}) =>
        h(
          CpLink,
          {
            href: edit.url({site: row.original.id}),
          },
          () =>
            h(
              'div',
              {
                class: 'flex gap-2',
              },
              [
                h('craft-indicator', {
                  variant: row.original.enabled ? 'success' : 'empty',
                }),
                h('span', getValue()),
              ]
            )
        ),
    }),
    columnHelper.accessor('handle', {
      header: () => t('Handle'),
      cell: (info) => h('code', info.getValue()),
    }),
    columnHelper.accessor('enabled', {
      header: () => t('Status'),
      cell: (info) =>
        h(
          Badge,
          {
            variant: info.getValue() ? 'success' : 'default',
          },
          () => (info.getValue() ? t('Enabled') : t('Disabled'))
        ),
    }),
    columnHelper.accessor('language', {
      header: () => t('Language'),
      cell: (info) => h('code', info.getValue()),
    }),
    columnHelper.accessor('primary', {
      header: () => t('Primary'),
      cell: (info) =>
        info.getValue()
          ? h('craft-icon', {
              name: 'check',
            })
          : '',
    }),
    columnHelper.accessor('baseUrl', {
      header: () => t('Base URL'),
      cell: (info) => h('code', info.getValue()),
    }),
    columnHelper.accessor('group.name', {
      id: 'group',
      header: () => t('Group'),
    }),
    columnHelper.actions(({row}) => [
      h(DeleteSiteButton, {
        site: row.original,
        disabled: row.original.primary,
        class: 'whitespace-normal',
      }),
    ]),
  ]);

  const sitesTable = useVueTable({
    get data() {
      return sites.value;
    },
    get columns() {
      return columns.value;
    },
    state: {
      get columnVisibility() {
        return {
          actions: !readOnly,
        };
      },
    },
    getCoreRowModel: getCoreRowModel<Site>(),
    getRowId: (row) => row.id.toString(),
    enableSorting: false,
    defaultColumn: {
      // @ts-expect-error — this is technically invalid, but gives us the behavior we want
      size: 'auto',
      minSize: 50,
      maxSize: 200,
    },
  });

  function handleDeleteClick() {
    if (
      props.group?.id &&
      // @TODO custom confirmation dialog?
      confirm(t('Are you sure you want to delete this group?'))
    ) {
      router.delete(destroy({groupId: props.group.id}));
    }
  }

  useAppLayout(() => ({fullWidth: true, title: props.title}));
</script>

<template>
  <LayoutSlot name="title">
    <div class="flex gap-2 items-center">
      <h1 class="title text-xl">
        {{ title }}
      </h1>

      <craft-action-menu v-if="group?.id && !readOnly">
        <craft-button type="button" icon size="small" slot="invoker">
          <craft-icon name="gear" :label="t('Site group Actions')"></craft-icon>
        </craft-button>

        <div slot="content">
          <craft-action-item @click.prevent="openModal('update')">
            {{ t('Rename Group') }}
          </craft-action-item>
          <craft-action-item
            variant="danger"
            :disabled="sites.length > 0"
            @click.prevent="handleDeleteClick"
          >
            {{ t('Delete Group') }}
          </craft-action-item>
        </div>
      </craft-action-menu>
    </div>
  </LayoutSlot>
  <LayoutSlot name="actions">
    <CpLink
      v-if="!readOnly"
      as="craft-button"
      :href="create({}, {query: {groupId: group?.id}}).url"
      variant="accent"
      appearance="button"
    >
      <craft-icon name="plus" slot="prefix"></craft-icon>
      {{ t('New Site') }}
    </CpLink>
  </LayoutSlot>

  <LayoutSlot name="subnav-actions">
    <div class="mt-4 flex gap-2" v-if="!readOnly">
      <craft-button type="button" @click="openModal('create')" size="small">
        <craft-icon name="plus" slot="prefix"></craft-icon>
        {{ t('New Group') }}
      </craft-button>
    </div>
  </LayoutSlot>

  <craft-pane appearance="raised" padding="0" class="@container">
    <template v-if="readOnly">
      <CalloutReadOnly />
    </template>

    <AdminTable
      :table="sitesTable"
      :read-only="readOnly"
      :reorderable="!!group?.id"
      @reorder="handleReorder"
    >
      <template #empty-row>
        <Empty icon="light/earth-americas" :label="t('No sites exist yet.')">
          <CpLink
            v-if="!readOnly"
            as="craft-button"
            :href="create({}, {query: {groupId: group?.id}}).url"
            appearance="button"
          >
            <craft-icon name="plus" slot="prefix"></craft-icon>
            {{ t('New Site') }}
          </CpLink>
        </Empty>
      </template>
    </AdminTable>
  </craft-pane>

  <ModalForm
    :is-active="modalActive"
    @close="
      modalActive = false;
      form.reset();
    "
    @submit="saveGroup"
    :loading="form.processing"
  >
    <craft-input
      name="id"
      id="id"
      :model-value="form.id ?? ''"
      hidden-input
    ></craft-input>
    <Deferred data="nameTextExpanderTriggers">
      <template #fallback>
        <craft-input
          readonly
          name="readonly-name"
          :label="t('Group Name')"
          :help-text="t('What this group will be called in the control panel.')"
        >
          <div slot="after">
            <craft-callout
              variant="info"
              appearance="plain"
              class="p-0"
              icon="lightbulb"
            >
              {{ t('Type `$` to choose an environment variable.') }}
              <a
                href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
                >{{ t('Learn more') }}</a
              >
            </craft-callout>
          </div>
        </craft-input>
      </template>
      <CraftInput
        :label="t('Group Name')"
        id="name"
        name="name"
        required
        :help-text="t('What this group will be called in the control panel.')"
        :text-expander-triggers="nameTextExpanderTriggers"
        v-model="form.name"
        :error="form.errors?.name"
      >
        <craft-callout
          slot="after"
          variant="info"
          appearance="plain"
          class="p-0"
          icon="lightbulb"
        >
          {{ t('Type `$` to choose an environment variable.') }}
          <a
            href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
            >{{ t('Learn more') }}</a
          >
        </craft-callout>
      </CraftInput>
    </Deferred>
  </ModalForm>
</template>

<style scoped lang="scss">
  .title {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
  }
</style>
