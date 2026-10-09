<script setup lang="ts">
  import {computed} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import type {ActionItemLink} from '@/common/types';

  const props = defineProps<{
    label: string | null;
    url?: string | null;
    menuItems?: Array<{label: string; url: string}> | null;
  }>();

  const menuActions = computed<ActionItemLink[]>(
    () =>
      props.menuItems?.map((item) => ({
        type: 'link',
        href: item.url,
        label: item.label,
      })) ?? []
  );
</script>

<template>
  <CpButtonLink
    v-if="url"
    variant="primary"
    icon="plus"
    :href="url"
    :inertia="false"
    >{{ label }}</CpButtonLink
  >
  <ActionMenu v-else-if="menuActions.length" :actions="menuActions">
    <template #invoker="{attributes}">
      <craft-button
        type="button"
        variant="primary"
        icon="plus"
        v-bind="attributes"
      >
        {{ label }}
        <craft-icon name="chevron-down" slot="suffix"></craft-icon>
      </craft-button>
    </template>
  </ActionMenu>
</template>
