<script setup lang="ts">
  import {capitalize, t} from '@craftcms/cp';
  import type {PluginInfo} from '@/types/plugins';
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import PluginEdition from '@/components/Plugins/PluginEdition.vue';
  import CpLink from '@/components/CpLink.vue';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';

  const props = defineProps<{
    plugin: PluginInfo;
  }>();

  const page = usePage<{
    readOnly: boolean;
  }>();

  const showLicenseKey = computed(
    () => props.plugin.licenseKey && props.plugin.licenseKeyStatus !== 'unknown'
  );

  const licenseIssues = computed(() =>
    props.plugin.licenseIssues.map((issue) => {
      switch (issue) {
        case 'wrong_edition':
          return t('This license is for the {name} edition.', {
            name: capitalize(props.plugin.licensedEdition),
          });
        case 'no_trials':
          return t('Plugin trials are not allowed on this domain.');
        case 'mismatched':
          return t(
            'This license is tied to another Craft install. Visit {accountLink} to detach it, or <a href="{buyUrl}">buy a new license</a>.',
            {
              accountLink:
                '<a href="https://console.craftcms.com" rel="noopener" target="_blank">console.craftcms.com</a>',
              buyUrl: props.plugin.buyUrl,
            }
          );
        case 'astray':
          return t('This license isn’t allowed to run version {version}.', {
            version: props.plugin.version,
          });

        case 'required':
          return t('A license key is required.');
        default:
          return t('Your license key is invalid.');
      }
    })
  );
</script>

<template>
  <div class="cp-plugin">
    <div class="cp-plugin__icon" v-html="plugin.iconSvg"></div>
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
        <div class="max-w-[80ch] mb-1">
          <p>{{ plugin.description }}</p>
        </div>
      </template>

      <div>
        <ul v-if="plugin.links.length > 0" class="flex gap-3 items-base">
          <li v-for="link in plugin.links">
            <a :href="link.href" target="_blank" rel="noopener" class="flex gap-1 items-center">
              <craft-icon v-if="link.icon" :name="link.icon"></craft-icon>
              {{ link.text }}
            </a>
          </li>
        </ul>
      </div>

      <div class="flex gap-2 items-center my-4" v-if="showLicenseKey">
        <craft-input
          :value="plugin.licenseKey"
          class="font-mono"
          readonly
          :style="{
            width: `${plugin.licenseKey.length + 6}ch`,
          }"
        >
          <craft-copy-button
            slot="suffix"
            :value="plugin.licenseKey"
          ></craft-copy-button>
        </craft-input>

        <template v-if="!page.props.readOnly && plugin.buyUrl">
          <CpLink
            appearance="button"
            :inertia="false"
            v-if="plugin.licenseKeyStatus === 'trial'"
            :href="plugin.buyUrl"
            :variant="plugin.licenseIssues.length > 0 ? 'primary' : 'default'"
            >{{ t('Buy now') }}</CpLink
          >
        </template>
      </div>

      <template v-for="issue in licenseIssues">
        <div v-html="issue"></div>
      </template>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .cp-plugin {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: var(--c-spacing-lg);
    padding: var(--c-spacing-md);
  }

  .cp-plugin__icon :deep(svg) {
    width: 56px;
    height: 56px;
  }
</style>
