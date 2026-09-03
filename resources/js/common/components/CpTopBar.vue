<script setup lang="ts">
  import {ButtonVariant} from '@craftcms/ui';
  import {t} from '@craftcms/ui/utilities/translate';
  import {useGlobalSidebar} from '@/common/composables/useGlobalSidebar';
  import UserMenu from '@/common/components/UserMenu.vue';
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import type {CraftData} from '@/common/composables/useCraftData';
  import {index as generalSettings} from '@routes/cp/settings/general';
  import CpLink from '@/common/components/CpLink.vue';
  import Breadcrumbs, {
    type BreadcrumbItem,
  } from '@/common/components/Breadcrumbs.vue';
  import {fieldId} from '@/modules/forms/runtime';
  import {useMediaQuery} from '@vueuse/core';
  import SystemInfo from '@/common/components/SystemInfo.vue';
  import VarDump from '@/common/components/VarDump.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';

  // Passed in rather than read here: `crumbs` is a page prop, but whether there's
  // a context menu depends on the shell's own slots, which a child can't see.
  defineProps<{
    crumbs?: Array<BreadcrumbItem> | null;
    hasContextMenu?: boolean;
  }>();

  const isLarge = useMediaQuery('(min-width: 768px)');

  const {
    sidebar,
    toggle: toggleSidebar,
    width,
    icon: toggleIcon,
  } = useGlobalSidebar();

  const page = usePage<{craft: CraftData}>();
  const maintenanceMode = computed(() => page.props.craft.maintenanceMode);
  const devMode = computed(() => page.props.craft.devMode);
  const generalSettingsUrl = computed(() =>
    generalSettings.url({cpTrigger: page.props.craft.general.cpTrigger ?? ''})
  );

  // Deep-linked to the field itself, which `useFieldHighlight` scrolls to and
  // rings on arrival. Built from the same helper the field's id comes from, so
  // the badge and the field can't drift apart.
  const maintenanceModeUrl = computed(
    () => `${generalSettingsUrl.value}#${fieldId(['maintenanceMode'])}`
  );
</script>

<template>
  <div class="cp-top-bar">
    <div class="flex gap-1 items-center justify-between">
      <craft-button
        id="sidebar-toggle"
        type="button"
        size="small"
        :icon="isLarge ? toggleIcon : 'bars'"
        :variant="ButtonVariant.Outline"
        @click="toggleSidebar"
        :aria-label="t('Toggle menu')"
      >
      </craft-button>

      <div class="flex gap-3 items-center">
        <div class="flex gap-1 items-center">
          <template v-if="devMode">
            <craft-badge fill="warning">
              <craft-icon name="code" slot="prefix"></craft-icon>
              {{ t('Dev Mode') }}
            </craft-badge>
          </template>

          <template v-if="maintenanceMode">
            <CpLink :href="maintenanceModeUrl">
              <craft-badge fill="warning">
                <craft-icon name="person-digging" slot="prefix"></craft-icon>
                {{ t('Maintenance mode') }}
              </craft-badge>
            </CpLink>
          </template>
        </div>
        <craft-button
          icon
          :variant="ButtonVariant.Plain"
          type="button"
          size="small"
        >
          <craft-icon name="search" :label="t('Search')"></craft-icon>
        </craft-button>
        <UserMenu />
      </div>
    </div>
    <div class="flex gap-2 items-center">
      <SystemInfo v-if="isLarge" />
      <div
        class="py-1 flex flex-nowrap items-center gap-2"
        v-show="crumbs || hasContextMenu"
      >
        <span class="text-xs text-(--c-text-quiet)" v-if="isLarge">/</span>
        <Breadcrumbs v-if="crumbs" :items="crumbs" />
        <div v-show="hasContextMenu" class="context-menu-container">
          <LayoutSlotOutlet name="context-menu">
            <slot name="context-menu"></slot>
          </LayoutSlotOutlet>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
  .cp-top-bar {
    padding-block: calc(var(--spacing) * 2);
    padding-inline: calc(var(--spacing) * 2);
    background-color: var(--c-color-fill-quiet);
    color: var(--c-color-on-quiet);
    display: grid;
    gap: var(--spacing);
    // border-block-end: 1px solid
    //   color-mix(transparent 75%, var(--c-color-border-quiet));
  }
</style>
