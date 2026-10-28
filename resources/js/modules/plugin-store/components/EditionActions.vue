<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {router, usePage} from '@inertiajs/vue3';
  import {computed, shallowRef} from 'vue';
  import InstallController from '@actions/PluginStore/InstallController';
  import PluginsController from '@actions/PluginsController';
  import {legacy} from '@actions/PluginStore/PluginStoreController';
  import useCraftData from '@/common/composables/useCraftData';
  import {usePluginLicenseInfo} from '../composables/usePluginStoreData';
  import {resolveEditionActions} from '../editionActions';
  import {formatPrice} from '../pricing';
  import type {Plugin, PluginEdition, PluginStoreContext} from '../types';

  const {plugin, edition} = defineProps<{
    plugin: Plugin;
    edition: PluginEdition;
  }>();

  const page = usePage<{context: PluginStoreContext}>();
  const {csrfTokenName, csrfTokenValue} = useCraftData();
  const {licenseInfoFor, refresh} = usePluginLicenseInfo();

  const licenseInfo = computed(() => licenseInfoFor(plugin.handle));

  const actions = computed(() =>
    resolveEditionActions({
      plugin,
      edition,
      licenseInfo: licenseInfo.value,
      context: page.props.context,
      // The cart still lives in the standalone app, which adds the edition when it's handed one.
      inCart: false,
    })
  );

  /** What the edition costs as an upgrade from the one already licensed. */
  const upgradePrice = computed(() => {
    const info = licenseInfo.value;
    const licensed = plugin.editions.find(
      (e) => e.handle === info?.licensedEdition
    );

    if (
      !licensed ||
      licensed.handle === edition.handle ||
      !Number(licensed.price) ||
      !['valid', 'astray'].includes(info?.licenseKeyStatus ?? '')
    ) {
      return null;
    }

    return Number(edition.price) - Number(licensed.price);
  });

  const installedLabel = computed(() => {
    switch (actions.value.install?.kind) {
      case 'installed':
        return t('Installed');
      case 'installed-trial':
        return t('Installed as a trial');
      default:
        return null;
    }
  });

  const installButton = computed(() => {
    const install = actions.value.install;
    return install && 'method' in install ? install : null;
  });

  const installLabels = {
    install: t('Install'),
    try: t('Try'),
    reactivate: t('Reactivate'),
  };

  const noticeText = computed(() => {
    const notice = actions.value.notice;

    switch (notice?.kind) {
      case 'partially-compatible':
        return t(
          'Only up to {version} is compatible with your version of Craft.',
          {
            version: notice.version,
          }
        );
      case 'incompatible':
        return t('This plugin isn’t compatible with your version of Craft.');
      case 'php':
        return t(
          'This plugin requires PHP {v1}, but your environment is currently running {v2}.',
          {v1: notice.required, v2: notice.current}
        );
      case 'composer-php':
        return t(
          'This plugin requires PHP {v1}, but your composer.json file is currently set to {v2}.',
          {v1: notice.required, v2: notice.current}
        );
      case 'cms-edition':
        return t('This plugin requires Craft CMS {name} edition.', {
          name:
            notice.edition.charAt(0).toUpperCase() + notice.edition.slice(1),
        });
      case 'abandoned':
        return t('This plugin is no longer maintained.');
      default:
        return null;
    }
  });

  const switching = shallowRef(false);
  const installing = shallowRef(false);

  function switchEdition(): void {
    router.post(
      PluginsController.switchEdition({handle: plugin.handle}).url,
      {edition: edition.handle},
      {
        preserveState: true,
        preserveScroll: true,
        onStart: () => (switching.value = true),
        onFinish: () => {
          switching.value = false;
          void refresh();
        },
      }
    );
  }
</script>

<template>
  <div class="grid gap-sm">
    <template v-if="actions.purchase">
      <craft-button
        v-if="actions.purchase.kind === 'add-to-cart'"
        variant="primary"
        :href="
          actions.purchase.disabled
            ? null
            : legacy({path: `buy/${plugin.handle}/${edition.handle}`}).url
        "
        .disabled="actions.purchase.disabled"
      >
        <template v-if="upgradePrice !== null">
          <del>{{ formatPrice(edition.price) }}</del>
          {{ formatPrice(upgradePrice) }}
        </template>
        <template v-else>{{ formatPrice(edition.price) }}</template>
      </craft-button>
      <craft-button
        v-else-if="actions.purchase.kind === 'licensed'"
        variant="primary"
        disabled
      >
        {{ t('Licensed') }}
      </craft-button>
      <craft-button v-else variant="primary" icon="check" disabled>
        {{ t('Already in your cart') }}
      </craft-button>
    </template>

    <craft-button v-if="installedLabel" icon="check" disabled>
      {{ installedLabel }}
    </craft-button>

    <template v-else-if="installButton">
      <craft-button
        v-if="installButton.method === 'switch'"
        :variant="installButton.primary ? 'primary' : 'outline'"
        .disabled="installButton.disabled"
        .loading="switching"
        @click="switchEdition"
      >
        {{ installLabels[installButton.kind] }}
      </craft-button>

      <!-- The installer is a full page of its own, so this posts rather than visits. -->
      <form
        v-else
        method="post"
        :action="InstallController.index().url"
        class="grid"
        @submit="installing = true"
      >
        <input
          v-if="csrfTokenName && csrfTokenValue"
          type="hidden"
          :name="csrfTokenName"
          :value="csrfTokenValue"
        />
        <input type="hidden" name="packageName" :value="plugin.packageName" />
        <input type="hidden" name="handle" :value="plugin.handle" />
        <input type="hidden" name="edition" :value="edition.handle" />
        <input
          type="hidden"
          name="version"
          :value="`^${plugin.latestCompatibleVersion}`"
        />
        <craft-button
          type="submit"
          :variant="installButton.primary ? 'primary' : 'outline'"
          .disabled="installButton.disabled"
          .loading="installing"
        >
          {{ installLabels[installButton.kind] }}
        </craft-button>
      </form>
    </template>

    <p v-if="noticeText" class="edition-actions__notice">{{ noticeText }}</p>
  </div>
</template>

<style scoped>
  .edition-actions__notice {
    margin: 0;
    color: var(--c-text-quiet);
  }
</style>
