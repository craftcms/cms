<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {CustomizeMenu} from '@/common/composables/useCraftData';
  import {openCustomizeSources} from '@/common/composables/useCustomizeSourcesHandlers';
  import type {ActionItems} from '@/common/types';

  /**
   * The nav footer's Customize menu: the CP Navigation settings, and each
   * element type's sources.
   *
   * It sits beside Collapse rather than in the nav, so it's there on every
   * page, and a customized nav that hides Settings, or a nav without an
   * element index, can't take either customization out of reach.
   */
  const props = defineProps<{
    menu: CustomizeMenu;
  }>();

  const actions = computed<ActionItems>(() => [
    {
      type: 'link',
      label: `${props.menu.navigation.label}…`,
      href: props.menu.navigation.href,
      icon: props.menu.navigation.icon ?? undefined,
    },
    ...(props.menu.sources.length
      ? [
          {type: 'hr' as const},
          {
            type: 'group' as const,
            heading: t('Customize sources'),
            items: props.menu.sources.map((source) => ({
              label: `${source.label}…`,
              icon: source.icon ?? undefined,
              onClick: () => openCustomizeSources(source.elementType),
            })),
          },
        ]
      : []),
  ]);
</script>

<template>
  <ActionMenu
    class="customize-menu"
    placement="top-start"
    icon="gear"
    :label="t('Customize')"
    :actions="actions"
    :inherit="false"
  />
</template>
