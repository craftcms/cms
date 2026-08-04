<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {PluginInfo} from '@/modules/plugin-manager/types/plugins';
  import {computed} from 'vue';
  import PluginEdition from '@/modules/plugin-manager/components/PluginEdition.vue';
  import PluginLicenseStatusIcon from '@/modules/plugin-manager/components/PluginLicenseStatusIcon.vue';
  import PluginLinks from '@/modules/plugin-manager/components/PluginLinks.vue';
  import PluginLicenseIssues from '@/modules/plugin-manager/components/PluginLicenseIssues.vue';
  import PluginLicenseInput from '@/modules/plugin-manager/components/PluginLicenseInput.vue';

  const props = defineProps<{
    plugin: PluginInfo;
  }>();

  const showLicenseKey = computed(
    () => props.plugin.licenseKey && props.plugin.licenseKeyStatus !== 'unknown'
  );

  const renewalHtml = computed(() => {
    return [
      t('This license has expired.'),
      t(`<a href="{renewalUrl}">Renew now</a> for another year of updates.`, {
        renewalUrl: props.plugin.renewalUrl,
      }),
    ].join(' ');
  });
</script>

<template>
  <div class="cp-plugin">
    <div class="cp-plugin__icon">
      <component
        :is="plugin.pluginStoreUrl ? 'a' : 'div'"
        :href="plugin.pluginStoreUrl"
        class="relative"
        target="_blank"
      >
        <template v-if="plugin.iconUrl">
          <img :src="plugin.iconUrl" alt="" />
        </template>
        <template v-else-if="plugin.iconSvg">
          <span v-html="plugin.iconSvg"></span>
        </template>
        <PluginLicenseStatusIcon
          v-if="
            plugin.licenseKeyStatus === 'valid' ||
            plugin.licenseIssues.length > 0
          "
          class="license-key-status"
          :status="plugin.licenseIssues.length === 0 ? 'valid' : 'invalid'"
        />
      </component>
    </div>
    <div>
      <div class="flex gap-2 items-baseline mb-1">
        <h2>{{ plugin.name }}</h2>

        <template v-if="plugin.hasMultipleEditions || plugin.isTrial">
          <PluginEdition
            :url="plugin.upgradeAvailable ? plugin.pluginStoreUrl : null"
            :edition="plugin.hasMultipleEditions ? plugin.edition : null"
            :is-trial="plugin.isTrial"
            class="self-center"
          />
        </template>

        <div class="font-mono text-xs">
          {{ plugin.version }}
        </div>
      </div>

      <template v-if="plugin.description">
        <div class="mb-1">
          <p>{{ plugin.description }}</p>
        </div>
      </template>

      <div>
        <PluginLinks :plugin="plugin" />
      </div>

      <div class="my-4" v-if="showLicenseKey">
        <PluginLicenseInput :plugin="plugin" />

        <PluginLicenseIssues
          :plugin="plugin"
          v-if="plugin.licenseIssues.length > 0"
        />

        <template v-if="plugin.expired">
          <craft-callout
            variant="warning"
            appearance="plain"
            class="p-0"
            v-html="renewalHtml"
          />
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .cp-plugin {
    width: 100%;
    max-width: 80ch;
    display: grid;
    grid-template-columns: 56px 1fr;
    gap: var(--c-spacing-lg);
    padding: var(--c-spacing-md);
  }

  .license-key-status {
    display: block;
    position: absolute;
    inset-inline-end: calc(2rem / 16 * -1);
    inset-block-end: calc(5rem / 16);
    width: calc(20rem / 16);
    height: calc(20rem / 16);
  }

  .cp-plugin__icon :deep(svg) {
    width: 56px;
    height: 56px;
  }
</style>
