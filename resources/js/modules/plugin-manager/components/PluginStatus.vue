<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {PluginInfo} from '@/modules/plugin-manager/types/plugins';
  import Badge from '@/common/components/Badge.vue';

  defineProps<{
    plugin: PluginInfo & {isComposerInstalled?: boolean};
  }>();
</script>

<template>
  <template v-if="plugin.isEnabled">
    <Badge variant="success">{{ t('Installed') }}</Badge>
  </template>
  <template v-else-if="!plugin.isComposerInstalled">
    <Badge>{{ t('Missing') }}</Badge>
  </template>
  <template v-else-if="plugin.isInstalled">
    <div class="flex gap-1 items-center">
      <Badge variant="warning">{{ t('Disabled') }}</Badge>
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
      <Badge>{{ t('Not Installed') }}</Badge>
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
