<script lang="ts">
  // Defined in `@/common/types` so plain modules can build crumbs too — a
  // `.ts` file can't import a type out of an SFC. Re-exported here because
  // this is where callers have always reached for it.
  export type {BreadcrumbItem} from '@/common/types';
</script>

<script setup lang="ts">
  import CpLink from '@/common/components/CpLink.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {t} from '@craftcms/ui';
  import {computed, getCurrentInstance} from 'vue';

  const props = withDefaults(
    defineProps<{
      items: Array<BreadcrumbItem>;
      separator?: string;
    }>(),
    {
      separator: '/',
    }
  );

  const emit = defineEmits<{navigate: [url: string]}>();

  /**
   * Crumbs arrive from anywhere — core controllers, elements, plugins — and
   * the older shape spelled the link `url`. Resolving it here keeps a producer
   * written against that working.
   */
  const crumbs = computed(() =>
    props.items.map((item) => ({...item, href: item.href ?? item.url ?? null}))
  );

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
  <craft-breadcrumbs :label="t('Breadcrumbs')" class="text-xs">
    <craft-breadcrumb-item
      v-for="(item, idx) in crumbs"
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
        v-if="item.items?.length"
        slot="suffix"
        icon="chevron-down"
        :actions="item.items"
        :label="t('Actions')"
      />
    </craft-breadcrumb-item>
  </craft-breadcrumbs>
</template>

<style scoped lang="scss"></style>
