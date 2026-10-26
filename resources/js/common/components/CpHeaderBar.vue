<script setup lang="ts">
  import {ButtonVariant} from '@craftcms/ui';
  import {t} from '@craftcms/ui/utilities/translate';
  import {useGlobalSidebar} from '@/common/composables/useGlobalSidebar';
  import UserMenu from '@/common/components/UserMenu.vue';
  import {computed, type ComponentPublicInstance} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import type {CraftData} from '@/common/composables/useCraftData';
  import {index as generalSettings} from '@routes/cp/settings/general';
  import CpLink from '@/common/components/CpLink.vue';
  import Breadcrumbs, {
    type BreadcrumbItem,
  } from '@/common/components/Breadcrumbs.vue';
  import {fieldId} from '@/modules/ui/runtime';
  import SystemInfo from '@/common/components/SystemInfo.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import {cpBreakpoints} from '@/common/composables/useCpBreakpoints';

  // Passed in rather than read here: `crumbs` is a page prop, but whether there's
  // a context menu depends on the shell's own slots, which a child can't see.
  defineProps<{
    crumbs?: Array<BreadcrumbItem> | null;
    hasContextMenu?: boolean;
  }>();

  const isLarge = cpBreakpoints.greaterOrEqual('lg');

  type TopBarSection = 'start' | 'indicators' | 'end' | 'breadcrumbs';

  // DOM order must match visual order per breakpoint for keyboard tab order —
  // CSS Grid areas (below) place these sections independent of source order,
  // so source order has to be made breakpoint-aware too.
  const sectionOrder = computed<TopBarSection[]>(() =>
    isLarge.value
      ? ['breadcrumbs', 'indicators', 'end']
      : ['start', 'indicators', 'end', 'breadcrumbs']
  );

  const {toggle: toggleSidebar, toggleButton} = useGlobalSidebar();

  // The composable returns focus here when the floating sidebar closes, and it
  // can't reach into a template of its own to find the button.
  function registerToggle(el: Element | ComponentPublicInstance | null): void {
    toggleButton.value = (el as HTMLElement | null) ?? null;
  }

  const page = usePage<{
    craft: CraftData;
    bridged?: 'screen' | 'elementIndex';
  }>();
  const maintenanceMode = computed(() => page.props.craft.maintenanceMode);
  const notifications = computed(() => page.props.craft.general.notifications);
  const devMode = computed(() => page.props.craft.devMode);

  /**
   * Which bridge drew this screen, for the badge below. Only the two bridged
   * pages set it, so a ported page shows nothing.
   */
  const bridged = computed(() =>
    page.props.bridged === 'screen'
      ? t('Bridged screen')
      : page.props.bridged === 'elementIndex'
        ? t('Bridged index')
        : null
  );
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
  <header class="cp-header-bar" data-theme="dark">
    <template v-for="section in sectionOrder" :key="section">
      <div class="cp-header-bar__start" v-if="section === 'start'">
        <craft-button
          :ref="registerToggle"
          id="sidebar-toggle"
          type="button"
          size="small"
          icon="bars"
          :variant="ButtonVariant.Outline"
          @click="toggleSidebar"
          :aria-label="t('Toggle menu')"
        >
        </craft-button>
      </div>

      <div
        class="cp-header-bar__indicators"
        v-else-if="section === 'indicators'"
      >
        <template v-if="devMode">
          <craft-badge fill="warning">
            <craft-icon name="code" slot="prefix"></craft-icon>
            {{ t('Dev Mode') }}
          </craft-badge>
        </template>

        <template v-if="devMode && bridged">
          <craft-badge fill="violet">
            <craft-icon name="bridge" slot="prefix"></craft-icon>
            {{ bridged }}
          </craft-badge>
        </template>

        <template v-if="maintenanceMode">
          <craft-badge fill="warning">
            <craft-icon name="person-digging" slot="prefix"></craft-icon>
            <CpLink :href="maintenanceModeUrl">{{
              t('Maintenance mode')
            }}</CpLink>
          </craft-badge>
        </template>
      </div>

      <div class="cp-header-bar__end" v-else-if="section === 'end'">
        <div class="flex gap-md items-center">
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

      <div
        class="cp-header-bar__breadcrumbs"
        v-else-if="section === 'breadcrumbs'"
      >
        <div class="flex gap-md items-center">
          <SystemInfo v-if="isLarge" />
          <div
            class="flex flex-nowrap items-center gap-md"
            v-show="crumbs || hasContextMenu"
          >
            <span class="text-xs text-(--c-text-quiet) px-sm" v-if="isLarge"
              >/</span
            >
            <Breadcrumbs v-if="crumbs" :items="crumbs" class="gap-md" />
            <div v-show="hasContextMenu" class="context-menu-container">
              <LayoutSlotOutlet name="context-menu">
                <slot name="context-menu"></slot>
              </LayoutSlotOutlet>
            </div>
          </div>
        </div>
      </div>
    </template>
  </header>
</template>

<style scoped>
  html:not([data-theme='dark']) .cp-header-bar {
    --c-surface-sunken: color-mix(
      in oklab,
      var(--color-base-50),
      var(--color-black) 10%
    );
  }

  .cp-header-bar {
    --badge-border-color: var(--c-surface-sunken);
    background-color: var(--c-surface-sunken);
    padding-block-start: calc(var(--c-spacing-sm) - 1px);
    padding-block-end: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-sm);
    padding-inline-end: calc(
      var(--c-spacing-sm) + var(--cp-scrollbar-width, 0px)
    );
    min-height: calc(42rem / 16);
    display: grid;
    gap: var(--spacing);
    grid-template-areas: 'start . indicators end' 'breadcrumbs breadcrumbs breadcrumbs breadcrumbs';
    grid-template-columns:
      var(--c-size-touch-target)
      1fr auto auto;
    grid-template-rows: repeat(2, auto);
    align-items: center;
    border-block-start: 1px solid rgba(0 0 0 / 0.25);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
    box-shadow: var(--c-shadow-xs), var(--c-shadow-lg);
    position: relative;
    z-index: var(--c-layer-header);

    @media (width >= var(--breakpoint-lg)) {
      padding-inline: var(--c-spacing-md);
      padding-inline-end: calc(
        var(--c-spacing-md) + var(--cp-scrollbar-width, 0px)
      );
      gap: calc(var(--spacing) * 3);
      grid-template-areas: 'breadcrumbs indicators end';
      grid-template-rows: auto;
      grid-template-columns: 1fr auto auto;
    }
  }

  .cp-header-bar__start {
    display: flex;
    justify-content: center;
    grid-area: start;
    margin-inline-start: calc(var(--spacing) * -2);
  }

  .cp-header-bar__end {
    grid-area: end;
  }

  .cp-header-bar__indicators {
    grid-area: indicators;
    display: flex;
    gap: var(--c-spacing-sm);
  }

  .cp-header-bar__breadcrumbs {
    --c-text-link: var(--c-text-default);
    --c-link-decoration: none;
    --c-link-decoration-hover: underline;
    grid-area: breadcrumbs;
    overflow: auto;
    /* Room inside the scroll area for focus rings, which it would clip. */
    padding: var(--c-spacing-sm);
    margin: calc(var(--c-spacing-sm) * -1);
  }

  .cp-header-bar__breadcrumbs
    :deep(craft-breadcrumb-item[aria-current='page']) {
    --c-chip-text: var(--color-slate-700);
    color: var(--color-slate-700);
  }
</style>
