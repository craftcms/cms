<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import CalloutReadOnly from '@/components/CalloutReadOnly.vue';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {computed, h, nextTick, ref, watch} from 'vue';
  import type {SelectItem, Site, SiteGroup} from '@/types';
  import ModalForm from '@/components/ModalForm.vue';
  import {Deferred, router, useForm} from '@inertiajs/vue3';
  import {destroy, store} from '@actions/Settings/SiteGroupsController.js';
  import {create, edit, reorder} from '@actions/Settings/SitesController';
  import DeleteSiteButton from '@/components/sites/DeleteSiteButton.vue';
  import CpLink from '@/components/CpLink.vue';
  import Badge from '@/components/Badge.vue';
  import {index} from '@routes/cp/settings/sites';
  import InputCombobox from '@/components/form/InputCombobox.vue';
  import IndexLayout from '@/layout/IndexLayout.vue';
  import {createCraftColumnHelper} from '@/components/AdminTable/createCraftColumnHelper';
  import Empty from '@/components/Empty.vue';

  const props = defineProps<{
    readOnly: boolean;
    group: SiteGroup | null;
    groups: Array<SiteGroup>;
    sites: Array<Site>;
    nameSuggestions?: Array<SelectItem>;
    flash: {
      success: string | null;
      error: string | null;
    };
  }>();

  const modalActive = ref(false);
  const columnHelper = createCraftColumnHelper<Site>();

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
  const sites = computed(() => {
    return siteIds.value
      .map((id) => props.sites.find((site) => site.id === id))
      .filter(Boolean);
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
            href: edit.url(row.original.id),
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
    getCoreRowModel: getCoreRowModel<Site>(),
    getRowId: (row) => row.id.toString(),
    enableSorting: false,
    defaultColumn: {
      // @ts-ignore this is technically invalid, but gives us the behavior we want
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

  const pageTitle = computed(() => {
    if (props.group?.name) {
      return props.group.name;
    }

    return t('Sites');
  });
</script>

<template>
  <IndexLayout :debug="{form, $props}" :full-width="true" :title="pageTitle">
    <template #title>
      <div class="flex gap-2 items-center">
        <h1 class="title text-xl">
          {{ pageTitle }}
        </h1>

        <craft-action-menu v-if="group?.id">
          <craft-button type="button" icon size="small" slot="invoker">
            <craft-icon
              name="gear"
              :label="t('Site group Actions')"
            ></craft-icon>
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
    </template>
    <template #actions>
      <CpLink
        as="craft-button"
        :href="create({query: {groupId: group?.id}}).url"
        variant="primary"
        appearance="button"
      >
        <craft-icon name="plus" slot="prefix"></craft-icon>
        {{ t('New Site') }}
      </CpLink>
    </template>

    <template #interior-nav="{state}">
      <nav>
        <craft-nav-list class="-mx-2">
          <craft-nav-item :href="index.url()" :active="!group">
            {{ t('All Sites') }}
          </craft-nav-item>
          <CpLink
            as="craft-nav-item"
            v-for="g in groups"
            :key="g.id"
            :href="index.url({query: {groupId: g.id}})"
            :active="group && g.id === group.id"
            block
          >
            {{ g.name }}
          </CpLink>
        </craft-nav-list>
      </nav>

      <div class="mt-4 flex gap-2">
        <craft-button type="button" @click="openModal('create')" size="small">
          <craft-icon name="plus" slot="prefix"></craft-icon>
          {{ t('New Group') }}
        </craft-button>
      </div>
    </template>

    <div>
      <template v-if="readOnly">
        <CalloutReadOnly />
      </template>

      <AdminTable
        :table="sitesTable"
        :read-only="readOnly"
        :reorderable="!!group?.id"
        spacing="relaxed"
        @reorder="handleReorder"
      >
        <template #empty-row>
          <Empty icon="light/earth-americas" :label="t('No sites exist yet.')">
            <CpLink
              as="craft-button"
              :href="create({query: {groupId: group?.id}}).url"
              appearance="button"
            >
              <craft-icon name="plus" slot="prefix"></craft-icon>
              {{ t('New Site') }}
            </CpLink>
          </Empty>
        </template>
      </AdminTable>
    </div>
  </IndexLayout>

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
      v-model="form.id"
      type="hidden"
    ></craft-input>
    <Deferred data="nameSuggestions">
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
              {{ t('This can begin with an environment variable.') }}
              <a
                href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
                >{{ t('Learn more') }}</a
              >
            </craft-callout>
          </div>
        </craft-input>
      </template>
      <craft-input
        :label="t('Group Name')"
        id="name"
        name="name"
        required
        :help-text="t('What this group will be called in the control panel.')"
        :has-feedback-for="form.errors?.name ? 'error' : ''"
      >
        <InputCombobox
          :options="nameSuggestions"
          v-model="form.name"
          slot="input"
        />
        <div slot="after">
          <craft-callout
            variant="info"
            appearance="plain"
            class="p-0"
            icon="lightbulb"
          >
            {{ t('This can begin with an environment variable.') }}
            <a
              href="https://craftcms.com/docs/5.x/configure.html#control-panel-settings"
              >{{ t('Learn more') }}</a
            >
          </craft-callout>
        </div>

        <div slot="feedback">
          <ul class="error-list" v-if="form.errors?.name">
            <li>{{ form.errors.name }}</li>
          </ul>
        </div>
      </craft-input>
    </Deferred>
  </ModalForm>
</template>

<style scoped lang="scss">
  .interior {
    display: grid;
    grid-template-columns: minmax(calc(120rem / 16), 16%) 1fr;
    gap: var(--c-spacing-md);
    align-items: start;
  }

  .title {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-md);
  }

  .separator {
    font-size: 0.8em;
    font-weight: 400;
    opacity: 0.5;
  }
</style>
