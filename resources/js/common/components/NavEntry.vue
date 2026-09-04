<script setup lang="ts">
  /**
   * One entry in a secondary nav, from the same descriptor the collapsed menu
   * renders as an action item.
   *
   * A descriptor with a link is a link; one without is a button, which is how
   * an element index's sources arrive — selecting a source is a partial visit
   * that keeps the list's scroll and state, not a navigation.
   */
  import {computed} from 'vue';
  import CpLink from '@/common/components/CpLink.vue';
  import type {ActionItemButton, ActionItemLink} from '@/common/types';

  const {item} = defineProps<{item: ActionItemLink | ActionItemButton}>();

  const attrs = computed(() =>
    Object.fromEntries(
      Object.entries(item.attrs ?? {}).filter(
        ([, value]) => value !== undefined
      )
    )
  );
</script>

<template>
  <CpLink
    v-if="item.type === 'link'"
    v-bind="attrs"
    as="craft-nav-item"
    :href="item.href"
    :inertia="!item.external"
    :active.prop="Boolean(item.selected)"
    :icon="item.icon"
    :indicator.prop="Boolean(item.indicator)"
    flush
    block
    @click="item.onClick"
    @mousedown="item.onMousedown"
  >
    {{ item.label }}
  </CpLink>

  <craft-nav-item
    v-else
    v-bind="attrs"
    :active.prop="Boolean(item.selected)"
    :icon="item.icon"
    :indicator.prop="Boolean(item.indicator)"
    :disabled="item.disabled"
    flush
    block
    @click="item.onClick"
    @mousedown="item.onMousedown"
  >
    {{ item.label }}
  </craft-nav-item>
</template>
