<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import type {PluginInfo} from '@/types/plugins';
  import ActionMenu from '@/components/ActionMenu.vue';
  import {computed} from 'vue';
  import type {ActionItemData} from '@/types';

  const props = defineProps<{
    plugin: PluginInfo;
  }>();

  const actions = computed(() => {
    const items: Array<ActionItemData> = [
      {
        icon: 'clipboard',
        label: t('Copy plugin handle'),
        action: 'craft:copy-to-clipboard',
        success: t('Copied!'),
        params: {
          value: props.plugin.handle,
        },
        onClick: (event) => {
          navigator.clipboard.writeText(props.plugin.handle);
        },
      },
    ];

    if (!props.plugin.isInstalled) {
      items.push({
        icon: 'plus',
        label: t('Install'),
        action: 'plugins/install-plugin',
        disabled: props.plugin.isForceDisabled,
        params: {
          pluginHandle: props.plugin.handle,
        },
      });

      items.push({
        icon: 'minus',
        label: t('Remove'),
        action: 'pluginstore/remove',
        variant: 'danger',
        params: {
          packageName: props.plugin.packageName,
        },
        confirm: t('Are you sure you want to remove {plugin}?', {
          plugin: props.plugin.name,
        }),
      });
    } else {
      if (props.plugin.isEnabled) {
        items.push({
          icon: 'circle-dashed',
          label: t('Disable'),
          action: 'plugins/disable-plugin',
          params: {
            pluginHandle: props.plugin.handle,
          },
        });

        items.push({
          icon: 'xmark',
          label: t('Uninstall'),
          variant: 'danger',
          params: {
            pluginHandle: props.plugin.handle,
          },
          confirm: t(
            'Are you sure you want to uninstall {plugin}? You will lose all of its associated data.',
            {
              plugin: props.plugin.name,
            }
          ),
        });
      } else {
        items.push({
          icon: 'circle',
          label: t('Enable'),
          action: 'plugins/enable-plugin',
          params: {pluginHandle: props.plugin.handle},
          disabled: props.plugin.isForceDisabled,
        });
      }
    }

    return items;
  });
</script>

<template>
  <ActionMenu :actions="actions"></ActionMenu>
</template>

<style scoped lang="scss"></style>
