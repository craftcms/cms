<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {h} from 'vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {useCraftTable} from '@/modules/admin-table/craftTable';
  import {
    create,
    destroy,
    edit,
  } from '@actions/Settings/Users/UserGroupsController';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {router} from '@inertiajs/vue3';
  import type {UserGroup} from '@/common/types';
  import CpContainer from '@/common/components/CpContainer.vue';

  const props = defineProps<{
    groups: Array<UserGroup>;
    readOnly: boolean;
  }>();

  const columnHelper = createCraftColumnHelper<UserGroup>();
  const table = useCraftTable({
    get columns() {
      return [
        columnHelper.link('name', {
          header: t('Name'),
          props: ({row}) => ({
            href: edit({userGroup: row.original.id}).url,
            inertia: true,
          }),
        }),
        columnHelper.handle('handle'),
        columnHelper.actions(({row}) => [
          h(DeleteButton, {
            confirm: t('Are you sure you want to delete "{name}"?', {
              name: row.original.name,
            }),
            onClick: () =>
              router
                .optimistic<{groups: Array<UserGroup>}>(({groups}) => ({
                  groups: groups.filter(({id}) => id !== row.original.id),
                }))
                .delete(destroy({groupId: row.original.id})),
          }),
        ]),
      ];
    },
    get data() {
      return props.groups;
    },
  });
</script>

<template>
  <LayoutSlot name="content-actions">
    <CpButtonLink :href="create().url" icon="plus" variant="primary">{{
      t('New user group')
    }}</CpButtonLink>
  </LayoutSlot>

  <CpContainer class="@container">
    <AdminTable :table="table">
      <template #empty-row>
        <craft-empty icon="users" :label="t('No groups exist yet.')">
          <CpButtonLink :href="create().url" icon="plus">{{
            t('New user group')
          }}</CpButtonLink>
        </craft-empty>
      </template>
    </AdminTable>
  </CpContainer>
</template>
