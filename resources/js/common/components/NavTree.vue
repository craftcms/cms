<script setup lang="ts">
  /**
   * One level of the CP navigation, rendered recursively.
   *
   * PROTOTYPE. Nothing on the server produces a tree this deep yet — the nav
   * prop is one level plus whatever a plugin hands over — so this is driven by
   * fixtures (see `NavTree.stories.ts`) while the shape of the navigation map
   * is still being settled.
   *
   * Flyouts and indentation do different jobs. A flyout is for *getting*
   * somewhere: it reaches any branch from wherever you are without disturbing
   * the sidebar. Indentation is for *being* somewhere: the branch you're in
   * stays expanded so its shape, and your place in it, is on screen.
   *
   * So the trail to the selected item renders inline and open, and every other
   * branch flyouts on hover. Depth doesn't come into it — only one branch is
   * ever expanded, so the column never has to hold more than one level of
   * nesting per level of trail.
   *
   * A `group` is a heading, not a destination: it never opens a flyout of its
   * own, so its children render wherever the group itself landed — inline in
   * the sidebar, or inside its parent's flyout.
   */
  import CpLink from '@/common/components/CpLink.vue';

  type NavNode = CraftCms.Cms.Cp.Data.ActionItem;

  const {
    items,
    mode = 'trail',
    iconOnly = false,
  } = defineProps<{
    items: Array<NavNode>;
    /**
     * `trail` expands the branch you're in and flyouts the rest. `flyout` and
     * `inline` force one or the other, which is mostly useful for comparing
     * them in Storybook.
     */
    mode?: 'trail' | 'flyout' | 'inline';
    /** Collapsed to a rail: labels become tooltips and every subnav flyouts. */
    iconOnly?: boolean;
  }>();

  /** `subnav` is `false` when the server hasn't resolved this branch yet. */
  function childrenOf(item: NavNode): Array<NavNode> {
    return Array.isArray(item.subnav) ? item.subnav : [];
  }

  /** Whether the selection is this item or anywhere beneath it. */
  function onTrail(item: NavNode): boolean {
    return item.selected || childrenOf(item).some(onTrail);
  }

  function expanded(item: NavNode): boolean {
    if (item.group) {
      return true;
    }

    return mode === 'inline' || (mode === 'trail' && onTrail(item));
  }

  function subnavDisplay(item: NavNode): 'inline' | 'flyout' {
    return expanded(item) ? 'inline' : 'flyout';
  }

  /**
   * Expanded branches start open. A group has no toggle, so this is ignored
   * there; `craft-nav-item` re-reads it when it changes, which is what lets
   * the open branch follow the selection across an Inertia visit.
   */
  function initialState(item: NavNode): 'open' | 'closed' {
    return expanded(item) ? 'open' : 'closed';
  }
</script>

<template>
  <template v-for="item in items" :key="item.href ?? item.label ?? ''">
    <!-- A group heads its children rather than being somewhere to go, so it
      has no href and renders as a static, non-collapsible heading. -->
    <craft-nav-item
      v-if="item.group || !item.href"
      :group="item.group || undefined"
      :icon="item.icon || undefined"
      :icon-only="iconOnly || undefined"
      :subnav-display="subnavDisplay(item)"
      :initial-state="initialState(item)"
      :active.prop="item.selected"
      :indicator.prop="!!item.badgeCount"
    >
      {{ item.label }}

      <craft-nav-list v-if="childrenOf(item).length" slot="subnav">
        <NavTree :items="childrenOf(item)" :mode="mode" />
      </craft-nav-list>
    </craft-nav-item>

    <CpLink
      v-else
      as="craft-nav-item"
      :href="item.href"
      :icon="item.icon || undefined"
      :icon-only="iconOnly || undefined"
      :subnav-display="subnavDisplay(item)"
      :initial-state="initialState(item)"
      :active.prop="item.selected"
      :indicator.prop="!!item.badgeCount"
      :external.prop="item.external"
      :inertia="!item.external"
    >
      {{ item.label }}

      <craft-nav-list v-if="childrenOf(item).length" slot="subnav">
        <NavTree :items="childrenOf(item)" :mode="mode" />
      </craft-nav-list>
    </CpLink>
  </template>
</template>
