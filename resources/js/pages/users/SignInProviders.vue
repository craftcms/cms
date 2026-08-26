<script setup lang="ts">
  import {h, ref} from 'vue';
  import {router, useHttp, usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/ui';
  import {connect, destroy} from '@actions/Users/SignInProvidersController';
  import {getCoreRowModel, useVueTable} from '@tanstack/vue-table';
  import {createCraftColumnHelper} from '@/modules/admin-table/helpers/createCraftColumnHelper';
  import AdminTable from '@/modules/admin-table/components/AdminTable.vue';
  import Badge from '@/common/components/Badge.vue';
  import {elevatedSessionManager} from '@/modules/auth/elevated-session';
  import UserScreen from '@/modules/user/components/UserScreen.vue';

  defineOptions({
    inheritAttrs: false,
  });

  const page =
    usePage<CraftCms.Cms.Http.ViewModels.UserSignInProvidersViewModel>();

  type Provider = (typeof page.props.providers)[number];
  type ConnectResponse = {url: string};

  const processingProvider = ref<string | null>(null);
  const connectRequest = useHttp<Record<string, never>, ConnectResponse>({});
  const disconnectRequest = useHttp<
    Record<string, never>,
    Record<string, never>
  >({});

  async function connectProvider(provider: Provider) {
    if (!provider.canConnect) {
      return;
    }

    processingProvider.value = provider.handle;

    try {
      const response = await elevatedSessionManager.run(() =>
        connectRequest.post(connect({provider: provider.handle}).url)
      );

      if (response) {
        window.location.href = response.url;
      }
    } finally {
      processingProvider.value = null;
    }
  }

  async function disconnectProvider(provider: Provider) {
    if (!provider.connected) {
      return;
    }

    if (!confirm(provider.disconnectWarning ?? t('Are you sure?'))) {
      return;
    }

    processingProvider.value = provider.handle;

    try {
      const response = await elevatedSessionManager.run(() =>
        disconnectRequest.delete(destroy({provider: provider.handle}).url)
      );

      if (response) {
        router.reload();
      }
    } finally {
      processingProvider.value = null;
    }
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
                loading: processingProvider.value === row.original.handle,
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
  <UserScreen>
    <craft-pane padding="0" appearance="raised">
      <AdminTable :table="table" />
    </craft-pane>
  </UserScreen>
</template>
