<script setup lang="ts">
  import {computed, inject} from 'vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import CpButtonLink from '@/common/components/CpButtonLink.vue';
  import {openSlideout} from '@/common/slideouts';
  import type {ActionItemLink} from '@/common/types';
  import {AdminTableCreateSlideout} from '../create-slideout';

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

  const slideout = inject(AdminTableCreateSlideout, null);

  // A modified click still follows the link, to open the screen in a tab.
  function openInSlideout(event: MouseEvent): void {
    if (
      !props.url ||
      !slideout?.value ||
      event.altKey ||
      event.ctrlKey ||
      event.metaKey ||
      event.shiftKey ||
      event.button !== 0
    ) {
      return;
    }

    event.preventDefault();
    void openSlideout(props.url, {
      width: slideout.value.width,
      onSaved: slideout.value.onSaved,
    });
  }
</script>

<template>
  <CpButtonLink
    v-if="url && slideout"
    variant="primary"
    icon="plus"
    :href="url"
    :inertia="false"
    @click="openInSlideout"
    >{{ label }}</CpButtonLink
  >
  <CpButtonLink v-else-if="url" variant="primary" icon="plus" :href="url">{{
    label
  }}</CpButtonLink>
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
