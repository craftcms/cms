<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {PluginInfo} from '@/modules/plugin-manager/types/plugins';

  defineProps<{
    plugin: PluginInfo & {isComposerInstalled?: boolean};
  }>();
</script>

<template>
  <template v-if="plugin.isEnabled">
    <craft-badge fill="success">{{ t('Installed') }}</craft-badge>
  </template>
  <template v-else-if="!plugin.isComposerInstalled">
    <craft-badge>{{ t('Missing') }}</craft-badge>
  </template>
  <template v-else-if="plugin.isInstalled">
    <div class="flex gap-1 items-center">
      <craft-badge fill="warning">{{ t('Disabled') }}</craft-badge>
      <template v-if="plugin.isForceDisabled">
        <craft-info-icon>
          {{
            t('{plugin} is disabled by the {setting} config setting.', {
              plugin: plugin.name,
              setting: 'disabledPlugins',
            })
          }}
        </craft-info-icon>
      </template>
    </div>
  </template>
  <template v-else>
    <div class="flex gap-1 items-center">
      <craft-badge>{{ t('Not Installed') }}</craft-badge>
      <template v-if="plugin.isForceDisabled">
        <craft-info-icon>
          {{
            t(
              '{plugin} can’t be installed due to the {setting} config setting.',
              {
                plugin: plugin.name,
                setting: 'disabledPlugins',
              }
            )
          }}
        </craft-info-icon>
      </template>
    </div>
  </template>
</template>

<style scoped lang="scss"></style>
