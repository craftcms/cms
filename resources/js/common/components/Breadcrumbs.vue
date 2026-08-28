<script lang="ts">
  import type {ActionItems} from '@/common/types';

  export interface BreadcrumbItem {
    href?: string | null;
    label?: string | null;
    /** Server-rendered crumb content, e.g. an element chip. */
    html?: string | null;
    icon?: string;
    /** Extra attributes for the crumb (e.g. drag-and-drop drop-target hooks). */
    attrs?: Record<string, string>;
    /** Optional per-crumb action menu (e.g. the current folder's actions). */
    actions?: ActionItems;
  }
</script>

<script setup lang="ts">
  import CpLink from '@/common/components/CpLink.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {t} from '@craftcms/ui';
  import {computed, getCurrentInstance} from 'vue';

  withDefaults(
    defineProps<{
      items: Array<BreadcrumbItem>;
      separator?: string;
    }>(),
    {
      separator: '/',
    }
  );

  const emit = defineEmits<{navigate: [url: string]}>();

  // Opt-in SPA navigation: when a parent listens for `navigate`, intercept
  // plain left-clicks and hand the URL up (e.g. to preserve the current view
  // state) instead of letting CpLink do a full Inertia visit. Without a
  // listener, breadcrumbs behave as ordinary CpLinks.
  const instance = getCurrentInstance();
  const interceptNavigation = computed(
    () => !!instance?.vnode.props?.onNavigate
  );

  function onNavigate(event: MouseEvent, url: string) {
    // Leave modified clicks (open in new tab/window) to the real href.
    if (
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey ||
      event.button !== 0
    ) {
      return;
    }

    event.preventDefault();
    emit('navigate', url);
  }
</script>

<template>
  <craft-breadcrumbs :label="t('Breadcrumbs')" class="cp:text-xs">
    <craft-breadcrumb-item
      v-for="(item, idx) in items"
      :key="idx"
      v-bind="item.attrs"
    >
      <template v-if="item.icon">
        <craft-icon :name="item.icon" slot="prefix"></craft-icon>
      </template>
      <template v-if="item.html">
        <DynamicHtmlRenderer :html="item.html" />
      </template>
      <template v-else-if="item.href">
        <CpLink
          :href="item.href"
          :inertia="interceptNavigation ? false : undefined"
          @click="interceptNavigation && onNavigate($event, item.href)"
          >{{ item.label }}</CpLink
        >
      </template>
      <template v-else>
        {{ item.label }}
      </template>
      <ActionMenu
        v-if="item.actions?.length"
        slot="suffix"
        icon="chevron-down"
        :actions="item.actions"
        :label="t('Actions')"
      />
    </craft-breadcrumb-item>
  </craft-breadcrumbs>
</template>

<style scoped lang="scss"></style>
