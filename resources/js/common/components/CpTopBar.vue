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
  import {cpBreakpoints} from '@/common/composables/useCpBreakpoints';

  // Passed in rather than read here: `crumbs` is a page prop, but whether there's
  // a context menu depends on the shell's own slots, which a child can't see.
  defineProps<{
    crumbs?: Array<BreadcrumbItem> | null;
    hasContextMenu?: boolean;
  }>();

  const isLarge = cpBreakpoints.greaterOrEqual('lg');

  const {toggle: toggleSidebar, icon: toggleIcon} = useGlobalSidebar();

  const page = usePage<{craft: CraftData}>();
  const maintenanceMode = computed(() => page.props.craft.maintenanceMode);
  const notifications = computed(() => page.props.craft.general.notifications);
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
    <div class="cp-top-bar__start">
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
    </div>

    <div class="cp-top-bar__indicators">
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

    <div class="cp-top-bar__end">
      <div class="flex gap-2 items-center">
        <craft-button
          icon
          :variant="ButtonVariant.Plain"
          type="button"
          size="small"
        >
          <craft-icon name="search" :label="t('Search')"></craft-icon>
        </craft-button>
        <cp-notification-center
          :notifications.prop="notifications"
        ></cp-notification-center>
        <UserMenu />
      </div>
    </div>

    <div class="cp-top-bar__breadcrumbs">
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
  </div>
</template>

<style scoped lang="scss">
  .cp-top-bar {
    padding-block: calc(var(--spacing) * 1);
    padding-inline: calc(var(--spacing) * 1);
    color: var(--c-color-on-quiet);
    display: grid;
    gap: var(--spacing);
    grid-template-areas: 'start . indicators end' 'breadcrumbs breadcrumbs breadcrumbs breadcrumbs';
    grid-template-columns:
      calc(var(--global-sidebar-collapsed-width) - (var(--spacing) * 1))
      1fr auto auto;
    grid-template-rows: repeat(2, auto);
    align-items: center;

    background-color: color-mix(
      var(--c-color-fill-quiet),
      var(--c-color-fill-loud) 20%
    );

    // TODO: consolidate breakpoints
    @media screen and (min-width: 768px) {
      gap: calc(var(--spacing) * 3);
      grid-template-areas: 'start breadcrumbs indicators end';
      grid-template-rows: auto;
    }
  }

  .cp-top-bar__start {
    display: flex;
    justify-content: center;
    grid-area: start;
    margin-inline-start: calc(var(--spacing) * -2);
  }

  .cp-top-bar__end {
    grid-area: end;
  }

  .cp-top-bar__indicators {
    grid-area: indicators;
  }

  .cp-top-bar__breadcrumbs {
    grid-area: breadcrumbs;
  }
</style>
