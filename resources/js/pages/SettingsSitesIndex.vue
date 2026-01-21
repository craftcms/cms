<script setup lang="ts">
  import {CraftCombobox, t} from '@craftcms/cp';
  import AppLayout from '@/layout/AppLayout.vue';
  import CalloutReadOnly from '@/components/CalloutReadOnly.vue';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {createColumnHelper, getCoreRowModel, useVueTable,} from '@tanstack/vue-table';
  import {computed, h, ref} from 'vue';
  import type {SelectItem, Site, SiteGroup} from '@/types';
  import ModalForm from '@/components/ModalForm.vue';
  import {Deferred, router, useForm} from '@inertiajs/vue3';
  import {destroy, store} from '@actions/Settings/SiteGroupsController.js';
  import SiteGroupActions from '@/components/SiteGroupActions.vue';
  import {create, edit} from '@actions/Settings/SitesController';
  import DeleteSiteButton from '@/components/DeleteSiteButton.vue';
  import CpLink from '@/components/CpLink.vue';
  import Badge from '@/components/Badge.vue';
  import {index} from '@routes/cp/settings/sites';

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
  const columnHelper = createColumnHelper<Site>();

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

  const columns = ref([
    columnHelper.accessor('name', {
      header: () => t('Name'),
      cell: ({row, getValue}) =>
        h(
          CpLink,
          {
            href: edit.url(row.original.id),
          },
          () => h(
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
          () => info.getValue() ? t('Enabled') : t('Disabled')
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
      header: () => t('Group'),
    }),
    columnHelper.display({
      id: 'delete',
      cell: ({row}) =>
        h(
          'div',
          {
            class: 'flex justify-end gap-2',
          },
          h(DeleteSiteButton, {
            site: row.original,
            class: 'whitespace-normal',
          })
        ),
      meta: {
        wrap: true,
      },
    }),
  ]);

  const sitesTable = useVueTable({
    get data() {
      return props.sites;
    },
    get columns() {
      return columns.value;
    },
    getCoreRowModel: getCoreRowModel<Site>(),
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
  <AppLayout :debug="{form, $props}" :full-width="true" :title="pageTitle">
    <template #title>
      <div class="flex gap-2 items-center">
        <h1 class="title text-xl">
          {{ pageTitle }}
        </h1>
        <SiteGroupActions
          class="inline-block"
          v-if="group"
          @click:rename="openModal('update')"
          @click:delete="handleDeleteClick"
        />
      </div>
    </template>
    <template #actions>
      <CpLink
        :href="create({query: {groupId: group?.id}}).url"
        variant="primary"
        appearance="button"
      >
        <craft-icon name="plus" slot="prefix"></craft-icon>
        {{ t('New Site') }}
      </CpLink>
    </template>

    <div class="interior">
      <div class="">
        <nav>
          <craft-nav-list>
            <craft-nav-item :url="index.url()" :active="!group">
              {{ t('All Sites') }}
            </craft-nav-item>
            <CpLink
              as="craft-nav-item"
              v-for="g in groups"
              :key="g.id"
              :href="index.url({query: {groupId: g.id}})"
              :active="group && g.id === group.id"
              block
              suffix-only-on-hover
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
      </div>
      <div
        class="bg-white border border-border-subtle rounded-sm shadow-sm overflow-auto"
      >
        <div>
          <template v-if="readOnly">
            <CalloutReadOnly />
          </template>

          <template v-if="sites.length">
            <AdminTable :table="sitesTable"></AdminTable>
          </template>
          <template v-else>
            <div class="py-20">
              <div
                class="w-[60ch] mx-auto text-center grid gap-3 justify-items-center text-gray-500"
              >
                <craft-icon
                  name="light/earth-americas"
                  style="font-size: calc(48rem / 16)"
                ></craft-icon>
                <p>{{ t('No sites exist for this group yet.') }}</p>
                <craft-button>
                  <craft-icon name="plus" slot="prefix"></craft-icon>
                  {{ t('New site') }}
                </craft-button>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </AppLayout>

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
      <craft-combobox
        .requireOptionMatch="false"
        :label="t('Group Name')"
        id="name"
        name="name"
        required
        :help-text="t('What this group will be called in the control panel.')"
        :has-feedback-for="form.errors?.name ? 'error' : ''"
        show-all-on-empty
        v-model="form.name"
        @model-value-changed="form.name = $event.target?.modelValue"
      >
        <template v-for="(group, idx) in nameSuggestions" :key="idx">
          <craft-option
            v-for="suggestion in group.data"
            :key="suggestion.name"
            .choiceValue="suggestion.name"
            .hint="suggestion.hint"
            >{{ suggestion.name }}</craft-option
          >
        </template>
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
      </craft-combobox>
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
