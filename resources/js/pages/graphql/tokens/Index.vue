<script setup lang="ts">
  import {h} from 'vue';
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import CpLink from '@/common/components/CpLink.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {router} from '@inertiajs/vue3';
  import {create, destroy, edit} from '@actions/Gql/TokensController';
  import Empty from '@/common/components/Empty.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';

  export interface TokenData {
    id: number;
    name: string;
    schemaId: any;
    accessToken: string;
    enabled: boolean;
    expiryDate: any;
    lastUsed: any;
    dateCreated: any;
    uid: string;
    isTemporary: boolean;
    schema: any;
    isValid: boolean;
    isExpired: boolean;
    isPublic: boolean;
    scope: any;
  }

  const props = defineProps<{
    tokens: {
      data: Array<TokenData>;
    };
    dates: any;
    readOnly: boolean;
  }>();

  const columnHelper = createCraftColumnHelper<TokenData>();
  const table = useVueTable({
    get columns() {
      return [
        columnHelper.link('name', {
          header: t('Name'),
          props: ({row}) => ({
            href: edit({tokenId: row.original.id}).url,
          }),
        }),
        columnHelper.date('lastUsed', {
          header: t('Last Used'),
        }),
        columnHelper.date('expiryDate', {
          header: t('Expires'),
        }),
        columnHelper.actions(({row}) => [
          h(DeleteButton, {
            confirm: t('Are you sure you want to delete the “{name}” token?', {
              name: row.original.name,
            }),
            onClick: () =>
              router
                .optimistic<{tokens: {data: Array<TokenData>}}>(({tokens}) => ({
                  tokens: {
                    ...tokens,
                    data: tokens.data.filter(({id}) => id !== row.original.id),
                  },
                }))
                .delete(destroy({tokenId: row.original.id})),
          }),
        ]),
      ];
    },
    get data() {
      return props.tokens.data;
    },
    state: {
      get columnVisibility() {
        return {
          name: true,
          lastUsed: true,
          expiryDate: true,
          actions: !props.readOnly,
        };
      },
    },
    enableSorting: false,
    getCoreRowModel: getCoreRowModel<TokenData>(),
  });
</script>

<template>
  <LayoutSlot name="actions">
    <CpLink
      :href="create().url"
      icon="plus"
      appearance="button"
      variant="accent"
      >{{ t('New token') }}</CpLink
    >
  </LayoutSlot>
  <craft-pane padding="0" appearance="raised">
    <AdminTable :table="table">
      <template #empty-row>
        <Empty :label="t('No GraphQL tokens exist yet.')">
          <CpLink :href="create().url" icon="plus" appearance="button">{{
            t('New token')
          }}</CpLink>
        </Empty>
      </template>
    </AdminTable>
  </craft-pane>
</template>
