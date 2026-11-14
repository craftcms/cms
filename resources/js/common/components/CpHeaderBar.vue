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
  import AssistantToggle from '@/modules/ai/assistant/AssistantToggle.vue';
  import CommandPaletteTrigger from '@/modules/command-palette/components/CommandPaletteTrigger.vue';

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

  const {
    toggle: toggleSidebar,
    toggleButton,
    collapsed: sidebarCollapsed,
    width: sidebarWidth,
  } = useGlobalSidebar();

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
  const hasAssistant = computed(
    () => page.props.craft.currentUser?.aiAssistant ?? false
  );

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
  <header
    class="cp-header-bar"
    :style="{'--cp-header-sidebar-width': sidebarWidth}"
  >
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
        <div class="cp-header-bar__actions flex gap-sm items-center">
          <div class="cp-header-bar__icons flex items-center">
            <CommandPaletteTrigger />
            <AssistantToggle v-if="hasAssistant" />
            <cp-notification-center
              :notifications.prop="notifications"
              :flush="isLarge ? 'inline-end' : undefined"
            ></cp-notification-center>
          </div>
          <div class="cp-header-bar__user">
            <UserMenu />
          </div>
        </div>
      </div>

      <div
        class="cp-header-bar__breadcrumbs"
        v-else-if="section === 'breadcrumbs'"
      >
        <div class="flex gap-md items-center">
          <SystemInfo
            v-if="isLarge"
            class="cp-header-bar__system"
            :class="{'cp-header-bar__system--collapsed': sidebarCollapsed}"
          />
          <div
            class="flex flex-nowrap items-center gap-md"
            v-show="crumbs || hasContextMenu"
          >
            <span
              v-if="isLarge && sidebarCollapsed"
              class="cp-header-bar__separator"
              aria-hidden="true"
              >/</span
            >
            <Breadcrumbs v-if="crumbs" :items="crumbs" />
            <div
              v-show="hasContextMenu"
              class="context-menu-container"
              data-command-palette-actions
            >
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
  .cp-header-bar {
    --badge-border-color: var(--c-surface-page);
    padding-block-start: var(--c-spacing-sm);
    padding-block-end: var(--c-spacing-sm);
    padding-inline: var(--c-spacing-md);
    padding-inline-end: calc(
      var(--c-spacing-md) + var(--cp-scrollbar-width, 0px)
    );
    min-height: calc(42rem / 16);
    display: grid;
    gap: var(--c-spacing-sm);
    grid-template-areas: 'start . indicators end' 'breadcrumbs breadcrumbs breadcrumbs breadcrumbs';
    grid-template-columns:
      var(--c-size-touch-target)
      1fr auto auto;
    grid-template-rows: repeat(2, auto);
    align-items: center;
    position: relative;
    z-index: var(--c-layer-header);

    @media (width < var(--breakpoint-lg)) {
      /* As much above the menu button as beside it. */
      padding-block-start: var(--c-spacing-md);
      border-block-end: 1px solid var(--c-color-neutral-border-quiet);
    }

    @media (width >= var(--breakpoint-lg)) {
      /* As tall as the collapsed sidebar and the details rail are wide, so the
         system icon sits in the corner square they make. */
      min-height: var(--cp-rail-width);
      padding-inline-start: var(--c-spacing-md);
      padding-inline-end: var(--cp-scrollbar-width, 0px);
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

  /* As wide as the details rail, so the avatar centers on its tabs and the
     icons end where the details pane does. */
  @media (width >= var(--breakpoint-lg)) {
    .cp-header-bar__actions {
      gap: 0;
    }

    .cp-header-bar__user {
      display: flex;
      justify-content: center;
      inline-size: var(--cp-rail-width);
    }
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

  /* The logo is a collapsed nav item's size and sits in its column, so it holds
     still as the sidebar collapses and the name starts where the collapsed
     content does. */
  .cp-header-bar__system {
    flex: none;
    inline-size: calc(var(--cp-header-sidebar-width) - var(--c-spacing-md) * 2);
  }

  .cp-header-bar__system--collapsed {
    inline-size: auto;
    max-inline-size: calc(var(--cp-sidebar-width) - var(--c-spacing-md) * 2);
  }

  /* Matches craft-breadcrumb-item's separators. */
  .cp-header-bar__separator {
    --_chevron-stroke: 1px;
    --_chevron-angle: 30deg;
    --_chevron-reach: calc(12rem / 16 * tan(var(--_chevron-angle)));
    --_chevron-arm: calc(12rem / 16 / cos(var(--_chevron-angle)));
    position: relative;
    display: inline-block;
    flex: none;
    inline-size: calc(var(--_chevron-reach) + var(--_chevron-stroke));
    block-size: calc(24rem / 16);
    color: var(--c-color-neutral-border-quiet);
    font-size: 0;

    &::before,
    &::after {
      content: '';
      position: absolute;
      inset-inline-start: calc(var(--_chevron-reach) / 2);
      block-size: var(--_chevron-arm);
      border-inline-start: var(--_chevron-stroke) solid currentColor;
    }

    &::before {
      inset-block-start: calc((12rem / 16 - var(--_chevron-arm)) / 2);
      rotate: calc(var(--_chevron-angle) * -1);
    }

    &::after {
      inset-block-end: calc((12rem / 16 - var(--_chevron-arm)) / 2);
      rotate: var(--_chevron-angle);
    }

    &:dir(rtl) {
      scale: -1 1;
    }
  }

  /* Every crumb but the current one, back to full strength on hover. */
  .cp-header-bar__breadcrumbs
    :deep(craft-breadcrumb-item:not([aria-current='page'])) {
    --c-text-link: var(--cp-chrome-text-color);
    color: var(--cp-chrome-text-color);

    &:hover {
      --c-text-link: var(--c-text-default);
      color: var(--c-text-default);
    }
  }

  .cp-header-bar__breadcrumbs
    :deep(craft-breadcrumb-item[aria-current='page']) {
    --c-chip-text: var(--color-slate-800);
    color: var(--color-slate-800);
  }

  /* Plain buttons draw their icons in this, so it recolors them alone. */
  .cp-header-bar__icons {
    --c-color-neutral-on-quiet: var(--cp-chrome-icon-color);
  }

  .cp-header-bar__breadcrumbs
    :deep(craft-breadcrumb-item > craft-icon[slot='prefix']) {
    --c-icon-size: var(--cp-chrome-icon-size);
    color: var(--cp-chrome-icon-color);
  }
</style>
