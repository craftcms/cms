<script setup lang="ts">
  /**
   * One entry in a secondary nav, from the same descriptor the collapsed menu
   * renders as an action item.
   *
   * A descriptor with a link is a link; one without is a button.
   *
   * A link that brings its own `onClick` owns the click: that's how an element
   * index's sources arrive — a real href, so the item is a focusable anchor,
   * but selecting one is a partial visit that keeps the list's scroll and
   * state rather than a navigation, so the handler takes it from there.
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
  <!-- Straight to the element when the descriptor owns its click: `CpLink`
    takes its props from Inertia's `Link`, which declares `onClick` — so a
    handler passed through it is swallowed as a prop rather than reaching the
    DOM. It still carries the href, which is what makes the item a focusable
    anchor rather than plain text. -->
  <CpLink
    v-if="item.type === 'link' && !item.onClick"
    v-bind="attrs"
    as="craft-nav-item"
    :href="item.href"
    :inertia="!item.external"
    :active.prop="Boolean(item.selected)"
    :icon="item.icon"
    :indicator.prop="Boolean(item.indicator)"
    flush
    block
  >
    {{ item.label }}
  </CpLink>

  <craft-nav-item
    v-else
    :href="item.type === 'link' ? item.href : undefined"
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
