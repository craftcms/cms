<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, ref, watch} from 'vue';
  import {useMediaQuery} from '@vueuse/core';
  import CpLink from '@/common/components/CpLink.vue';
  import ActionList from '@/common/components/ActionList.vue';
  import {navItemActions} from '@/common/composables/navActions';
  import type {ActionItems} from '@/common/types';

  const {items = [], actions = []} = defineProps<{
    items?: Array<CraftCms.Cms.Cp.Data.NavItem>;
    /**
     * Controls belonging to the nav, described rather than slotted. They render
     * as buttons under the expanded nav and as items at the end of the action
     * menu once it collapses — one definition, both shapes.
     */
    actions?: ActionItems;
  }>();

  const isLarge = useMediaQuery('(min-width: 768px)');

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
    ...navItemActions(items),
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
  <div
    v-if="!isLarge"
    class="p-1 bg-(--c-color-neutral-fill-normal) flex justify-between"
  >
    <!-- The `slot="content"` wrapper below is ours on purpose. `craft-popover`
      auto-wraps unslotted children into a container it creates once and
      appends itself; Vue keeps rendering against its own anchor in the host,
      so anything added to these lists afterwards lands outside that wrapper
      and never reaches the slot. Owning the wrapper means the auto-wrap never
      runs and Vue has a stable parent to patch.

      The comment sits out here for the same reason: that auto-wrap counts any
      non-empty child node, comments included. -->
    <craft-action-menu>
      <craft-button
        slot="invoker"
        type="button"
        icon="chevron-down"
        variant="plain"
        size="small"
        >{{ t('Sidebar') }}</craft-button
      >
      <div slot="content">
        <ActionList :actions="menuItems" as="craft-action-item" />
      </div>
    </craft-action-menu>

    <slot name="actions"></slot>
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
        <template v-for="(item, index) in items" :key="index">
          <template v-if="item.subnav">
            <craft-nav-item
              initial-state="open"
              block
              flush
              :group="item.group"
            >
              <span class="text-xs font-bold">{{ item.label }}</span>

              <craft-nav-list slot="subnav">
                <CpLink
                  v-for="(subitem, subindex) in item.subnav"
                  :key="subindex"
                  as="craft-nav-item"
                  :active.prop="subitem.selected"
                  :href="subitem.href ?? ''"
                  :inertia="!subitem.external"
                  :icon="subitem.icon ?? undefined"
                  :indicator.prop="subitem.badgeCount > 0"
                  flush
                  block
                >
                  {{ subitem.label }}
                </CpLink>
              </craft-nav-list>
            </craft-nav-item>
          </template>

          <template v-else>
            <CpLink
              as="craft-nav-item"
              :active.prop="item.selected"
              :href="item.href ?? ''"
              :inertia="!item.external"
              :icon="item.icon ?? undefined"
              :indicator.prop="item.badgeCount > 0"
              flush
              block
            >
              {{ item.label }}
            </CpLink>
          </template>
        </template>
      </craft-nav-list>
    </slot>
    <!-- Only while the nav is the presentation: below the large breakpoint
      the same actions are already at the end of the action menu above, and
      this element stays in the DOM translated off-screen. -->
    <div
      v-if="isLarge && actions.length"
      class="secondary-nav__actions flex flex-wrap gap-2 mt-2"
    >
      <ActionList :actions="actions" as="craft-button" size="small" />
    </div>
    <slot name="actions"></slot>
  </nav>
</template>

<style scoped lang="css"></style>
