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
   *
   * Keyed by what the crumb is rather than where it sits, numbered when
   * repeated, so a crumb keeps its switcher menu when crumbs before it change.
   */
  const crumbs = computed(() => {
    const seen = new Map<string, number>();

    return props.items.map((item) => {
      const href = item.href ?? item.url ?? null;
      const base = item.id ?? href ?? item.label ?? item.html ?? '';
      const count = seen.get(base) ?? 0;
      seen.set(base, count + 1);

      return {...item, href, key: count ? `${base}#${count}` : base};
    });
  });
</script>

<template>
  <craft-breadcrumbs :label="t('Breadcrumbs')" class="text-xs">
    <craft-breadcrumb-item
      v-for="item in crumbs"
      :key="item.key"
      v-bind="item.attrs"
    >
      <template v-if="item.icon">
        <craft-icon :name="item.icon" slot="prefix"></craft-icon>
      </template>
      <template v-if="item.html">
        <DynamicHtmlRenderer :html="item.html" />
      </template>
      <template v-else-if="item.href">
        <CpLink :href="item.href">
          <span class="text-box-trim">{{ item.label }}</span>
        </CpLink>
      </template>
      <span v-else class="text-box-trim">{{ item.label }}</span>
      <ActionMenu
        v-if="item.items?.length"
        slot="suffix"
        icon="chevron-down"
        :actions="item.items"
        :label="t('Actions')"
      >
        <!-- Its own invoker rather than the default one: a crumb's switcher
          sits beside text, so it's smaller and isn't pulled flush against the
          label. -->
        <template #invoker="{label, attributes}">
          <craft-button
            v-bind="attributes"
            type="button"
            variant="plain"
            size="xsmall"
            icon="chevron-down"
            :aria-label="label"
            inherit
          ></craft-button>
        </template>
      </ActionMenu>
    </craft-breadcrumb-item>
  </craft-breadcrumbs>
</template>

<style scoped lang="scss">
  craft-breadcrumb-item > .cp-link:hover {
    text-decoration: underline;
  }

  /* Prevent action menu buttons from looking like they take up extra space */
  craft-action-menu > craft-button[slot='invoker'] {
    margin-inline: -3px;
  }
</style>
