<script setup lang="ts">
  import {h, ref} from 'vue';
  import {router, usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import Pane from '@/common/components/Pane.vue';
  import {connect, destroy} from '@actions/Users/SignInProvidersController';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import Badge from '@/common/components/Badge.vue';

  defineOptions({
    inheritAttrs: false,
  });

  const page =
    usePage<CraftCms.Cms.Http.ViewModels.UserSignInProvidersViewModel>();

  type Provider = (typeof page.props.providers)[number];

  const processingProvider = ref<string | null>(null);

  function requireElevatedSession(onSuccess: () => void) {
    (Craft as any).elevatedSessionManager.requireElevatedSession(onSuccess);
  }

  function connectProvider(provider: Provider) {
    if (!provider.canConnect) {
      return;
    }

    requireElevatedSession(() => {
      window.location.href = connect({provider: provider.handle}).url;
    });
  }

  function disconnectProvider(provider: Provider) {
    if (!provider.connected) {
      return;
    }

    if (!confirm(provider.disconnectWarning ?? t('Are you sure?'))) {
      return;
    }

    requireElevatedSession(() => {
      processingProvider.value = provider.handle;

      router.delete(destroy({provider: provider.handle}), {
        preserveScroll: true,
        onFinish: () => {
          processingProvider.value = null;
        },
      });
    });
  }

  const columnHelper = createCraftColumnHelper<Provider>();
  const table = useVueTable<Provider>({
    get data() {
      return page.props.providers;
    },
    get columns() {
      return [
        columnHelper.display({
          id: 'name',
          header: t('Provider'),
          cell: ({row}) =>
            h('div', {class: 'flex items-center gap-1'}, [
              row.original.icon &&
                h('craft-icon', {
                  name: row.original.icon,
                  family: 'brands',
                }),
              row.original.name,
            ]),
        }),
        columnHelper.display({
          id: 'status',
          header: t('Status'),

          cell: ({row}) =>
            h(
              Badge,
              {
                variant: row.original.connected ? 'success' : 'default',
              },
              () =>
                row.original.connected ? t('Connected') : t('Not connected')
            ),
        }),
        columnHelper.actions(({row}) => [
          !row.original.connected &&
            h(
              'craft-button',
              {
                type: 'button',
                size: 'small',
                'aria-label': t('Connect {provider}', {
                  provider: row.original.name,
                }),
                disabled: !row.original.canConnect,
                onclick: () => connectProvider(row.original),
              },
              t('Connect')
            ),
          row.original.connected &&
            h(
              'craft-button',
              {
                type: 'button',
                size: 'small',
                'aria-label': t('Disconnect {provider}', {
                  provider: row.original.name,
                }),

                loading: processingProvider.value === row.original.handle,
                onclick: () => disconnectProvider(row.original),
              },
              t('Disconnect')
            ),
        ]),
      ];
    },
    getCoreRowModel: getCoreRowModel<Provider>(),
    enableSorting: false,
  });
</script>

<template>
  <Pane :padding="0" appearance="raised">
    <AdminTable :table="table" />
  </Pane>
</template>
