<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import AppLayout from '@/layout/AppLayout.vue';
  import CalloutReadOnly from '@/components/CalloutReadOnly.vue';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import {
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
  } from '@tanstack/vue-table';
  import {computed, h, ref} from 'vue';
  import type {Site, SiteGroup} from '@/types';

  const props = defineProps<{
    readOnly: boolean;
    group: SiteGroup | null;
    groups: Array<SiteGroup>;
    sites: Record<string, Site>;
  }>();

  const tableData = computed(() =>
    Object.keys(props.sites).map((key) => props.sites[key] as Site)
  );
  const columnHelper = createColumnHelper<Site>();

  const columns = ref([
    columnHelper.accessor('name', {
      header: () => t('Name'),
      cell: ({row, getValue}) =>
        h(
          'a',
          {
            href: `/admin/settings/sites/${row.original.id}`,
          },
          h(
            'div',
            {
              class: 'flex gap-2',
            },
            [
              h('craft-indicator', {
                variant: row.original.enabled ? 'success' : 'danger',
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
      cell: () =>
        h(
          'div',
          {
            class: 'flex justify-end gap-2',
          },
          h(
            'craft-button',
            {
              size: 'small',
              icon: true,
              type: 'button',
              appearance: 'plain',
              '@click': () => alert('To do'),
            },
            h('craft-icon', {
              name: 'x',
              label: t('Delete site'),
            })
          )
        ),
    }),
  ]);

  const sitesTable = useVueTable({
    get data() {
      return tableData.value;
    },
    get columns() {
      return columns.value;
    },
    getCoreRowModel: getCoreRowModel(),
    defaultColumn: {
      // @ts-ignore this is technically invalid, but gives us the behavior we want
      size: 'auto',
      minSize: 50,
      maxSize: 200,
    },
  });
</script>

<template>
  <AppLayout :title="t('Sites')" :debug="$props" :full-width="true">
    <template #actions>
      <craft-button variant="primary">
        <craft-icon name="plus" slot="prefix"></craft-icon>
        {{ t('New site') }}
      </craft-button>
    </template>

    <div class="interior">
      <div>
        <nav>
          <craft-nav-list>
            <craft-nav-item url="/admin/settings/sites" :active="!group">
              {{ t('All Sites') }}
            </craft-nav-item>
            <craft-nav-item
              v-for="g in groups"
              :key="g.id"
              :url="`/admin/settings/sites?groupId=${g.id}`"
              :active="group && g.id === group.id"
            >
              {{ g.name }}
            </craft-nav-item>
          </craft-nav-list>
        </nav>

        <div class="mt-4">
          <craft-button type="button" @click="() => console.log('To do')">
            <craft-icon name="plus" slot="prefix"></craft-icon>
            {{ t('New Group') }}
          </craft-button>
        </div>
      </div>
      <div class="bg-white border border-border-subtle rounded-sm shadow-sm">
        <div>
          <template v-if="readOnly">
            <CalloutReadOnly />
          </template>

          <AdminTable :table="sitesTable" v-if="sites"></AdminTable>
          <template v-else>
            <div>
              <p>{{ t('No sites exist for this group yet.') }}</p>
            </div>
          </template>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped lang="scss">
  .interior {
    display: grid;
    grid-template-columns: calc(180rem / 16) 1fr;
    gap: var(--c-spacing-lg);
  }
</style>
