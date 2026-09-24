<script setup lang="ts">
  import {h} from 'vue';
  import {t} from '@craftcms/ui';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {router} from '@inertiajs/vue3';
  import {create, destroy, edit} from '@actions/Gql/TokensController';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import CpContainer from '@/common/components/CpContainer.vue';

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
  <LayoutSlot name="content-actions">
    <CpButtonLink :href="create().url" icon="plus" variant="primary">{{
      t('New token')
    }}</CpButtonLink>
  </LayoutSlot>
  <CpContainer>
    <AdminTable :table="table">
      <template #empty-row>
        <craft-empty :label="t('No GraphQL tokens exist yet.')">
          <CpButtonLink :href="create().url" icon="plus">{{
            t('New token')
          }}</CpButtonLink>
        </craft-empty>
      </template>
    </AdminTable>
  </CpContainer>
</template>
