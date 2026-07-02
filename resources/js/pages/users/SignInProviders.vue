<script setup lang="ts">
  import {ref} from 'vue';
  import {router, usePage} from '@inertiajs/vue3';
  import {t} from '@craftcms/cp';
  import IndexLayout from '@/common/layouts/IndexLayout.vue';
  import {connect, destroy} from '@actions/Users/SignInProvidersController';

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
</script>

<template>
  <IndexLayout>
    <div class="grid gap-4 p-4">
      <div class="tableview">
        <table class="data fullwidth">
          <thead>
            <tr>
              <th scope="col">{{ t('Provider') }}</th>
              <th scope="col">{{ t('Status') }}</th>
              <th scope="col">
                <span class="visually-hidden">{{ t('Actions') }}</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="provider in page.props.providers" :key="provider.handle">
              <th scope="row">
                <div class="flex flex-col gap-2xs">
                  <span class="flex items-center gap-1">
                    <craft-icon
                      v-if="provider.icon"
                      :name="provider.icon"
                      family="brands"
                      aria-hidden="true"
                    />
                    <span>{{ provider.name }}</span>
                  </span>
                  <span v-if="provider.disabledReason" class="smalltext light">
                    {{ provider.disabledReason }}
                  </span>
                </div>
              </th>
              <td>
                {{ provider.connected ? t('Connected') : t('Not connected') }}
              </td>
              <td class="text-right">
                <craft-button
                  v-if="provider.connected"
                  type="button"
                  size="small"
                  :loading="processingProvider === provider.handle"
                  @click="disconnectProvider(provider)"
                >
                  {{ t('Disconnect') }}
                </craft-button>
                <craft-button
                  v-else
                  type="button"
                  size="small"
                  :disabled="!provider.canConnect"
                  @click="connectProvider(provider)"
                >
                  {{ t('Connect') }}
                </craft-button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </IndexLayout>
</template>
