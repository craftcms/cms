<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {h} from 'vue';
  import IndexLayout from '@/layout/IndexLayout.vue';
  import AdminTable from '@/components/AdminTable/AdminTable.vue';
  import Empty from '@/components/Empty.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {create, destroy, edit} from '@actions/Settings/UserGroupsController';
  import CpLink from '@/components/CpLink.vue';
  import {createCraftColumnHelper} from '@/components/AdminTable/createCraftColumnHelper';
  import DeleteButton from '@/components/AdminTable/DeleteButton.vue';
  import {router} from '@inertiajs/vue3';

  interface UserGroup {
    id: number;
    name: string;
    handle: string;
    description: string | null;
    uid: string;
  }

  const props = defineProps<{
    groups: Array<UserGroup>;
    subnav: Array<{
      url: string;
      label: string;
      active?: boolean;
      inertia?: boolean;
    }>;
    readOnly: boolean;
  }>();

  function deleteGroup(group: UserGroup) {
    if (
      confirm(
        t('Are you sure you want to delete "{name}"?', {name: group.name})
      )
    ) {
      router.delete(destroy(group.id));
    }
  }

  const columnHelper = createCraftColumnHelper<UserGroup>();
  const table = useVueTable({
    get columns() {
      return [
        columnHelper.link('name', {
          header: t('Name'),
          props: ({row}) => ({
            inertia: false,
            href: edit(row.original.id).url,
          }),
        }),
        columnHelper.handle('handle'),
        columnHelper.actions(({row}) => [
          h(DeleteButton, {onClick: () => deleteGroup(row.original)}),
        ]),
      ];
    },
    get data() {
      return props.groups;
    },
    state: {
      get columnVisibility() {
        return {};
      },
    },
    getCoreRowModel: getCoreRowModel<UserGroup>(),
  });
</script>

<template>
  <IndexLayout>
    <template #actions>
      <CpLink
        :inertia="false"
        :href="create().url"
        class="btn submit add icon"
        icon="plus"
        appearance="button"
        variant="primary"
        >{{ t('New user group') }}</CpLink
      >
    </template>

    <template #interior-nav>
      <craft-nav-list>
        <template v-for="(item, index) in subnav" :key="index">
          <CpLink
            as="craft-nav-item"
            :active="item.active ?? false"
            :href="item.url"
            :inertia="item.inertia ?? true"
            block
            flush
          >
            {{ item.label }}
          </CpLink>
        </template>
      </craft-nav-list>
    </template>

    <AdminTable :table="table">
      <template #empty-row>
        <Empty icon="users" :label="t('No groups exist yet.')">
          <CpLink
            :inertia="false"
            :href="create().url"
            class="btn submit add icon"
            icon="plus"
            appearance="button"
            >{{ t('New user group') }}</CpLink
          >
        </Empty>
      </template>
    </AdminTable>
  </IndexLayout>
</template>

<style scoped lang="scss"></style>
