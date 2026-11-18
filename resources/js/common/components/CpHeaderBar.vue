<script setup lang="ts">
  import {ButtonVariant} from '@craftcms/ui';
  import {t} from '@craftcms/ui/utilities/translate';
  import {useGlobalSidebar} from '@/common/composables/useGlobalSidebar';
  import UserMenu from '@/common/components/UserMenu.vue';
  import {computed, type ComponentPublicInstance} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import type {CraftData} from '@/common/composables/useCraftData';
  import Breadcrumbs, {
    type BreadcrumbItem,
  } from '@/common/components/Breadcrumbs.vue';
  import SystemInfo from '@/common/components/SystemInfo.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import {cpBreakpoints} from '@/common/composables/useCpBreakpoints';
  import AssistantToggle from '@/modules/ai/assistant/AssistantToggle.vue';
  import AboutCraftMenu from '@/common/components/AboutCraftMenu.vue';
  import CommandPaletteTrigger from '@/modules/command-palette/components/CommandPaletteTrigger.vue';

  // Passed in rather than read here: `crumbs` is a page prop, but whether there's
  // a context menu depends on the shell's own slots, which a child can't see.
  defineProps<{
    crumbs?: Array<BreadcrumbItem> | null;
    hasContextMenu?: boolean;
  }>();

  const isLarge = cpBreakpoints.greaterOrEqual('lg');

  type TopBarSection = 'start' | 'end' | 'breadcrumbs';

  // DOM order must match visual order per breakpoint for keyboard tab order —
  // CSS Grid areas (below) place these sections independent of source order,
  // so source order has to be made breakpoint-aware too.
  const sectionOrder = computed<TopBarSection[]>(() =>
    isLarge.value ? ['breadcrumbs', 'end'] : ['start', 'end', 'breadcrumbs']
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

      <div class="cp-header-bar__end" v-else-if="section === 'end'">
        <div class="cp-header-bar__actions flex gap-sm items-center">
          <div class="cp-header-bar__icons flex items-center">
            <craft-info-icon
              v-if="devMode"
              class="cp-header-bar__mode"
              icon="code"
              :label="t('Dev Mode')"
              >{{ t('Dev Mode is enabled') }}</craft-info-icon
            >
            <craft-badge
              v-if="devMode && bridged"
              class="cp-header-bar__bridged"
              fill="violet"
              size="small"
            >
              <craft-icon name="bridge" slot="prefix"></craft-icon>
              {{ bridged }}
            </craft-badge>
            <craft-info-icon
              v-if="maintenanceMode"
              class="cp-header-bar__mode"
              icon="person-digging"
              :label="t('Maintenance Mode')"
              >{{ t('Maintenance Mode is enabled') }}</craft-info-icon
            >
            <AboutCraftMenu />
            <CommandPaletteTrigger />
            <AssistantToggle v-if="hasAssistant" />
            <cp-notification-center
              :notifications.prop="notifications"
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
            <Breadcrumbs
              v-if="crumbs"
              :items="crumbs"
              class="cp-header-bar__crumbs"
              :class="{
                'cp-header-bar__crumbs--indented': isLarge && !sidebarCollapsed,
              }"
            />
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
    min-height: var(--cp-header-height);
    display: grid;
    gap: var(--c-spacing-sm);
    grid-template-areas: 'start . end' 'breadcrumbs breadcrumbs breadcrumbs';
    grid-template-columns:
      var(--c-size-touch-target)
      1fr auto;
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
      grid-template-areas: 'breadcrumbs end';
      grid-template-rows: auto;
      grid-template-columns: 1fr auto;
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

    /* The bell sits as far from the avatar as the avatar does from the
       details panel's first tab. */
    .cp-header-bar__icons {
      margin-inline-end: 18px;
    }

    .cp-header-bar__user {
      display: flex;
      justify-content: center;
      inline-size: var(--cp-rail-width);
    }
  }

  /* As tall as the edition badge beside it, with text as large, the same
     corners, and its text as far from its edges. */
  .cp-header-bar__bridged {
    --c-size-control-xs: 18px;
    --c-text-xs: 10px;
    --c-color-fill-quiet: transparent;
    --c-color-border-quiet: color-mix(
      in srgb,
      var(--c-color-border-normal) 70%,
      transparent
    );
  }

  .cp-header-bar__mode {
    --c-icon-size: var(--cp-chrome-icon-size);
    /* The light theme's quiet text is brown; the loud fill is amber, and
       still 4.6:1 against the header. */
    --c-color-neutral-on-quiet: light-dark(
      var(--c-color-warning-fill-loud),
      var(--c-color-warning-on-quiet)
    );
    margin-inline: -5.25px;
  }

  .cp-header-bar__bridged::part(badge) {
    border-radius: var(--c-radius-sm);
    padding-inline: 4.25px 5px;
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

  .cp-header-bar__crumbs {
    margin-inline-start: 1px;
  }

  /* In line with the content below them, which the page screen measures,
     past the content's 1px border. Collapsed, they follow the site's name
     instead. */
  .cp-header-bar__crumbs--indented {
    margin-inline-start: calc(var(--cp-content-start, 0px) + 1px);
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

  /* Each button trims the space either side of its badge or icon, so this is
     the space between them. */
  .cp-header-bar__icons {
    gap: var(--c-spacing-lg);

    @media (width < var(--breakpoint-lg)) {
      margin-inline-end: calc(var(--c-spacing-lg) - var(--c-spacing-sm));
    }
  }

  .cp-header-bar__breadcrumbs
    :deep(craft-breadcrumb-item > craft-icon[slot='prefix']) {
    --c-icon-size: var(--cp-chrome-icon-size);
    color: var(--cp-chrome-icon-color);
  }
</style>
