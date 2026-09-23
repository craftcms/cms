<script lang="ts">
  // Defined in `@/common/types` so plain modules can build crumbs too — a
  // `.ts` file can't import a type out of an SFC. Re-exported here because
  // this is where callers have always reached for it.
  export type {BreadcrumbItem} from '@/common/types';
</script>

<script setup lang="ts">
  import {computed} from 'vue';
  import type {BreadcrumbItem} from '@/common/types';
  import CpLink from '@/common/components/CpLink.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {t} from '@craftcms/ui';

  const props = withDefaults(
    defineProps<{
      items: Array<BreadcrumbItem>;
      separator?: string;
    }>(),
    {
      separator: '/',
    }
  );

  /**
   * Crumbs arrive from anywhere — core controllers, elements, plugins — and
   * the older shape spelled the link `url`. Resolving it here keeps a producer
   * written against that working.
   */
  const crumbs = computed(() =>
    props.items.map((item) => ({...item, href: item.href ?? item.url ?? null}))
  );
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
        <CpLink :href="item.href">{{ item.label }}</CpLink>
      </template>
      <template v-else>
        {{ item.label }}
      </template>
      <ActionMenu
        v-if="item.items?.length"
        slot="suffix"
        icon="chevron-down"
        :actions="item.items"
        button-size="xsmall"
        :label="t('Actions')"
      />
    </craft-breadcrumb-item>
  </craft-breadcrumbs>
</template>

<style scoped lang="scss"></style>
