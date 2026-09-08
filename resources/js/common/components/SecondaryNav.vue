<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import {computed, ref, watch} from 'vue';
  import {useMediaQuery} from '@vueuse/core';
  import CpLink from '@/common/components/CpLink.vue';
  import ActionList from '@/common/components/ActionList.vue';
  import type {ActionItems} from '@/common/types';
  import VarDump from '@/common/components/VarDump.vue';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import {cpBreakpoints} from '@/common/composables/useCpBreakpoints';

  const {items = [], actions = []} = defineProps<{
    /**
     * The nav itself, described rather than drawn.
     *
     * The same array renders twice — as a list while there's room for one,
     * and as the menu this collapses into below the large breakpoint — so
     * the two can't say different things. Callers with a `NavItem[]` map it
     * with `navItemActions()`; the element indexes describe their sources
     * directly, since selecting one is a partial visit rather than a link.
     */
    items?: ActionItems;
    /**
     * Controls belonging to the nav, described rather than slotted. They render
     * as buttons under the expanded nav and as items at the end of the action
     * menu once it collapses — one definition, both shapes.
     */
    actions?: ActionItems;
  }>();

  const isLarge = cpBreakpoints.greaterOrEqual('lg');

  /**
   * The nav flattened for the collapsed menu, which is one flat list.
   *
   * A `group` heads its children rather than being somewhere to go — its own
   * URL is typically `#` — so its children stand in for it. Anything else
   * keeps its entry, since its children are reachable from the page it leads
   * to.
   */
  /**
   * Everything the collapsed menu shows: the nav itself, then whatever
   * controls belong to it, behind a rule.
   *
   * One list rather than two so the checkmark gutter is decided once — split
   * up, the nav items would be indented for their marks and the controls
   * below the rule would not, and the labels wouldn't line up.
   *
   * The nav's own groups survive as headings over their items, so the menu
   * reads the same as the list it stands in for.
   */
  const menuItems = computed<ActionItems>(() => [
    ...items,
    ...(actions.length > 0 ? [{type: 'hr'} as const, ...actions] : []),
  ]);
  const navState = ref<'hidden' | 'floating' | 'visible'>('hidden');

  const toggleLabel = computed(() =>
    navState.value === 'hidden' ? t('Hide sidebar') : t('Show sidebar')
  );

  function toggleNav() {
    const visibleState = isLarge.value ? 'visible' : 'floating';
    navState.value = navState.value === 'hidden' ? visibleState : 'hidden';
  }

  /*
Nav states:
- Hidden
- Floating
- Visible
 */

  watch(
    isLarge,
    (newValue) => {
      /**
       * When transitioning from small to large or large to small make sure
       * we set the nav state accordingly
       */
      if (!newValue) {
        navState.value = navState.value === 'visible' ? 'floating' : 'hidden';
      } else {
        navState.value = 'visible';
      }
    },
    {immediate: true}
  );
</script>

<template>
  <div v-if="!isLarge" class="flex gap-1 p-1">
    <craft-popover
      class="flex-1 relative"
      placement="bottom-start"
      match-invoker-width
    >
      <craft-button
        slot="invoker"
        type="button"
        icon="chevron-down"
        size="small"
        align="start"
        class="w-full"
        >{{ t('Sidebar') }}</craft-button
      >

      <div slot="content">
        <ActionList :actions="items" as="craft-action-item" />
      </div>
    </craft-popover>

    <craft-action-menu>
      <craft-button type="button" size="small" slot="invoker">
        <craft-icon name="ellipsis" :label="t('Customize')"></craft-icon>
      </craft-button>
      <div slot="content">
        <ActionList :actions="actions" as="craft-action-item" size="small" />
        <slot name="actions"></slot>
      </div>
    </craft-action-menu>
  </div>
  <nav
    v-else
    :aria-label="t('Secondary')"
    :class="{
      'secondary-nav': true,
      'secondary-nav--floating': navState === 'floating',
      'secondary-nav--hidden': navState === 'hidden',
    }"
    id="nav-container"
  >
    <slot>
      <craft-nav-list v-if="items.length">
        <!-- `inline`: this is a list, not a nav you travel through, so every
          group stays open rather than waiting to be hovered. -->
        <ActionList :actions="items" as="craft-nav-item" mode="inline" />
      </craft-nav-list>
    </slot>
    <!-- Only while the nav is the presentation: below the large breakpoint
      the same actions are already at the end of the action menu above, and
      this element stays in the DOM translated off-screen. -->
    <div
      v-if="isLarge && actions.length"
      class="secondary-nav__actions flex flex-wrap gap-2 mt-4"
    >
      <ActionMenu :actions="actions" :button-variant="ButtonVariant.Outline" />
      <slot name="actions"></slot>
    </div>
  </nav>
</template>

<style scoped lang="css">
  .secondary-nav {
    position: sticky;
    inset-block-start: 0;
    padding: var(--c-spacing-md) var(--c-spacing-lg);
  }
</style>
