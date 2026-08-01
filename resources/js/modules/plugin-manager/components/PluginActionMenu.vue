<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import type {PluginInfo} from '@/modules/plugin-manager/types/plugins';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {computed, onMounted} from 'vue';
  import type {ActionItem} from '@/common/types';
  import {
    disable,
    enable,
    install,
    uninstall,
  } from '@actions/PluginsController';
  import RemoveController from '@actions/PluginStore/RemoveController';
  import {router} from '@inertiajs/vue3';

  const props = defineProps<{
    plugin: PluginInfo;
  }>();

  const actions = computed(() => {
    const items: Array<ActionItem> = [
      {
        icon: 'clipboard',
        label: t('Copy plugin handle'),
        action: {
          type: 'clipboard',
          value: props.plugin.handle,
        },
        feedback: {
          success: {
            message: t('Copied!'),
          },
        },
      },
      {
        icon: 'clipboard',
        label: t('Copy package name'),
        action: {
          type: 'clipboard',
          value: props.plugin.packageName,
        },
        feedback: {
          success: {
            message: t('Copied!'),
          },
        },
      },
      {type: 'hr'},
    ];

    if (!props.plugin.isInstalled) {
      items.push({
        icon: 'plus',
        label: t('Install'),
        action: {
          type: 'http',
          url: install({handle: props.plugin.handle}).url,
        },
        disabled: props.plugin.isForceDisabled,
      });

      items.push({
        icon: 'minus',
        label: t('Remove'),
        variant: 'danger',
        action: {
          type: 'event',
          name: 'action:remove-plugin',
          confirm: t('Are you sure you want to remove {plugin}?', {
            plugin: props.plugin.name,
          }),
          detail: {
            packageName: props.plugin.packageName,
          },
        },
      });
    } else {
      if (props.plugin.isEnabled) {
        items.push({
          icon: 'circle-dashed',
          label: t('Disable'),
          action: {
            type: 'http',
            url: disable({handle: props.plugin.handle}).url,
          },
        });

        items.push({
          icon: 'xmark',
          label: t('Uninstall'),
          variant: 'danger',
          action: {
            type: 'http',
            url: uninstall({handle: props.plugin.handle}).url,
            confirm: t(
              'Are you sure you want to uninstall {plugin}? You will lose all of its associated data.',
              {
                plugin: props.plugin.name,
              }
            ),
          },
        });
      } else {
        items.push({
          icon: 'circle',
          label: t('Enable'),
          action: {
            type: 'http',
            url: enable({handle: props.plugin.handle}).url,
          },
          disabled: props.plugin.isForceDisabled,
        });
      }
    }

    return items;
  });

  function handleRemove(event: CustomEvent) {
    const {detail} = event;

    router.post(RemoveController.index(), {
      packageName: detail.packageName,
    });
  }

  onMounted(() => {
    window.addEventListener('action:remove-plugin', handleRemove);
  });
</script>

<template>
  <ActionMenu :actions="actions" />
</template>

<style scoped lang="scss"></style>
