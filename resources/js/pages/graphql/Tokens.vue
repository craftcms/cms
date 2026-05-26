<script setup lang="ts">
  import {h} from 'vue';
  import {t} from '@craftcms/cp';
  import AppLayout from '@/common/layouts/AppLayout.vue';
  import Pane from '@/common/components/Pane.vue';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import CpLink from '@/common/components/CpLink.vue';
  import DeleteButton from '@/modules/admin-table/components/DeleteButton.vue';
  import {router} from '@inertiajs/vue3';
  import {create, destroy, edit} from '@actions/Gql/TokensController';
  import Empty from '@/common/components/Empty.vue';

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
    tokens: Array<TokenData>;
    dates: any;
    readOnly: boolean;
  }>();

  function deleteToken(token: TokenData) {
    if (
      confirm(
        t('Are you sure you want to delete the “{name}” token?', {
          name: token.name,
        })
      )
    ) {
      router.delete(destroy(token.id));
    }
  }

  const columnHelper = createCraftColumnHelper<TokenData>();
  const table = useVueTable({
    get columns() {
      return [
        columnHelper.link('name', {
          header: t('Name'),
          props: ({row}) => ({href: edit(row.original.id).url, inertia: false}),
        }),
        columnHelper.date('lastUsed', {
          header: t('Last Used'),
        }),
        columnHelper.date('expiryDate', {
          header: t('Expires'),
        }),
        columnHelper.actions(({row}) => [
          h(DeleteButton, {onClick: () => deleteToken(row.original)}),
        ]),
      ];
    },
    get data() {
      return props.tokens;
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
  <AppLayout>
    <template #actions>
      <CpLink
        :href="create().url"
        icon="plus"
        :inertia="false"
        appearance="button"
        variant="primary"
      >{{ t('New token') }}</CpLink
      >
    </template>
    <Pane :padding="0" appearance="raised">
      <AdminTable :table="table">
        <template #empty-row>
          <Empty :label="t('No GraphQL tokens exist yet.')">
            <CpLink
              :href="create().url"
              icon="plus"
              :inertia="false"
              appearance="button"
            >{{ t('New token') }}</CpLink
            >
          </Empty>
        </template>
      </AdminTable>
    </Pane>
  </AppLayout>
</template>

<style scoped lang="scss"></style>
